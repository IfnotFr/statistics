<?php

namespace Ifnot\Statistics;

use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Class Statistics
 */
class Statistics
{
    protected $query;

    protected $interval;

    protected $date_column;

    protected $grouping;

    protected $indicators;

    protected $cache;

    /**
     * @param $query
     */
    public function __construct($query)
    {
        $this->query = $query;
        $this->indicators = [];

        $this->interval = new Interval(Interval::$DAILY);

        $this->cache = null;
    }

    /**
     * @param $query
     * @return Statistics
     */
    public static function of($query)
    {
        return new Statistics($query);
    }

    /**
     * @param $step
     * @param Carbon $start
     * @param Carbon $end
     * @return $this
     */
    public function interval($step, Carbon $start = null, Carbon $end = null)
    {
        $this->cache = null;

        $this->interval->step($step);
        $this->interval->start(isset($start) ? $start : $this->getStartDateFromQuery(clone $this->query));
        $this->interval->end(isset($end) ? $end : $this->getEndDateFromQuery(clone $this->query));

        return $this;
    }

    /**
     * @param $query
     * @return mixed
     */
    protected function getStartDateFromQuery($query)
    {
        $date =  $query->select(DB::raw('MIN(' . $this->date_column . ') as `date`'))->first()->date;

        if(is_null($date)) {
            return Carbon::now();
        }
        else {
            return Carbon::createFromFormat('Y-m-d H:i:s', $date);
        }
    }

    /**
     * @param $query
     * @return mixed
     */
    protected function getEndDateFromQuery($query)
    {
        $date = $query->select(DB::raw('MAX(' . $this->date_column . ') as `date`'))->first()->date;

        if(is_null($date)) {
            return Carbon::now();
        }
        else {
            return Carbon::createFromFormat('Y-m-d H:i:s', $date);
        }
    }

    /**
     * @return Interval
     */
    public function getInterval()
    {
        return $this->interval;
    }

    /**
     * @param $name
     * @param $function
     * @param null $counting_method
     * @return $this
     */
    public function indicator($name, $function, $counting_method = null)
    {
        $this->cache = null;

        if (is_null($counting_method)) {
            $counting_method = Indicator::$COUNTER;
        }

        $this->indicators[$name] = new Indicator($name, $function, $counting_method);

        return $this;
    }

    /**
     * @param $column
     * @return $this
     */
    public function date($column)
    {
        $this->cache = null;

        $this->date_column = $column;

        return $this;
    }

    /**
     * @param $grouping
     * @return $this
     */
    public function group($grouping)
    {
        $this->cache = null;

        $this->grouping = $grouping;

        return $this;
    }

    /**
     * @return array
     */
    public function make()
    {
        if (! is_null($this->cache)) {
            return $this->cache;
        }

        // Configure the query for setting the date interval
        $query = $this->query->whereBetween($this->date_column, [
            $this->interval->start()->format('Y-m-d')." 00:00:00",
            $this->interval->end()->format('Y-m-d')." 23:59:59",
        ]);

        // Converting the query to builded array datas
        $datas = $this->getDatasFromQuery($query);

        // Caching and return the result
        return $this->cache = $datas;
    }

    /**
     * @param $baseQuery
     * @return array|mixed
     */
    protected function getDatasFromQuery($baseQuery)
    {
        $datas = [];

        $groupValues = $this->getGroupValues($this->query);

        // Filling datas with empty values on all range dates
        foreach ($groupValues as $groupValue) {
            foreach ($this->interval->getStepIndexes() as $index) {
                $datas[$groupValue][$index] = $this->getEmptyDatas();
            }
        }

        // Building datas with indicators functions
        $query = clone $baseQuery;
        foreach ($query->get() as $row) {
            // Date if already an instance of Carbon.
            if ($row->{$this->date_column} instanceof Carbon) {
                $date = $row->{$this->date_column};
            } // Date if an integer, convert it to timestamp.
            elseif (is_numeric($row->{$this->date_column})) {
                $date = Carbon::createFromTimestamp($row->{$this->date_column});
            } // Date is in year-month-day format.
            elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $row->{$this->date_column})) {
                $date = Carbon::createFromFormat('Y-m-d', $row->{$this->date_column})->startOfDay();
            }

            $index = $this->interval->getStepIndexFromDate($date);

            $datas[$this->getGroupValueForRow($row)][$index] = $this->getDatasForRow($row, $datas[$this->getGroupValueForRow($row)][$index]);
        }

        // Build values of indicators when all done
        foreach ($datas as $groupValue => $groupDatas) {
            foreach ($groupDatas as $index => $data) {
                foreach ($this->indicators as $name => $indicator) {
                    $datas[$groupValue][$index] = $indicator->buildValue($datas[$groupValue][$index]);
                }
            }

            // Converting datas to Collection
            $datas[$groupValue] = new Collection($datas[$groupValue]);
        }

        if (isset($this->grouping)) {
            return new Collection(array_except($datas, ""));
        } else {
            return $datas[""];
        }
    }

    /**
     * @return array
     */
    protected function getEmptyDatas()
    {
        $datas = [];

        foreach ($this->indicators as $name => $indicator) {
            $datas[$name] = $indicator->getDefaultValue();
        }

        return $datas;
    }

    /**
     * @param $row
     * @param $old
     * @return array
     */
    protected function getDatasForRow($row, $old)
    {
        $datas = [];

        foreach ($this->indicators as $name => $indicator) {
            $datas[$name] = $indicator->getValue($row, $old[$name]);
        }

        return $datas;
    }

    /**
     * @param $originalQuery
     * @return array
     */
    protected function getGroupValues($originalQuery)
    {
        $query = clone $originalQuery;

        if (is_callable($this->grouping)) {
            $groupValues = [];

            foreach ($query->get() as $row) {
                $groupValues[] = call_user_func_array($this->grouping, [$row]);
            }

            return array_unique($groupValues);
        } elseif (is_string($this->grouping)) {
            return $query->distinct()->pluck($this->grouping)->all();
        } else {
            return [''];
        }
    }

    /**
     * @param $row
     * @return mixed|string
     */
    protected function getGroupValueForRow($row)
    {
        if (is_callable($this->grouping)) {
            return call_user_func_array($this->grouping, [$row]);
        } elseif (is_string($this->grouping)) {
            return $row->{$this->grouping};
        } else {
            return '';
        }
    }
}
