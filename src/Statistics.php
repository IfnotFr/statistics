<?php
namespace WhiteFrame\Statistics;

use B2B\Core\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\Collection;

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
     *
     * @return Statistics
     */
    public static function of($query)
    {
        return new Statistics($query);
    }

    /**
     * @param Carbon $start
     * @param Carbon $end
     *
     * @return $this
     */
    public function interval($step, Carbon $start, Carbon $end)
    {
        $this->cache = null;

        $this->interval
            ->step($step)
            ->start($start)
            ->end($end);

        return $this;
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
     * @param $method
     *
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
     */
    public function date($column)
    {
        $this->cache = null;

        $this->date_column = $column;

        return $this;
    }

    /**
     * @param $column
     *
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
        if (!is_null($this->cache)) {
            return $this->cache;
        }

        // Configure the query for setting the date interval
        $query = $this->query->whereBetween($this->date_column, [
            $this->interval->start()->format('Y-m-d') . " 00:00:00",
            $this->interval->end()->format('Y-m-d') . " 23:59:59"
        ]);

        // Converting the query to builded array datas
        $datas = $this->getDatasFromQuery($query);

        // Caching and return the result
        return $this->cache = $datas;
    }

    protected function getDatasFromQuery(Builder $baseQuery)
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
            $index = $this->interval->getStepIndexFromDate($row->{$this->date_column});

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
            return $datas;
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
     *
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

    protected function getGroupValues($originalQuery)
    {
        $query = clone $originalQuery;

        if(is_callable($this->grouping)) {
            $groupValues = [];

            foreach($query->get() as $row) {
                $groupValues[] = call_user_func_array($this->grouping, [$row]);
            }

            return array_unique($groupValues);
        }
        elseif(is_string($this->grouping)) {
            return $query->distinct()->lists($this->grouping)->all();
        }
        else {
            return [''];
        }
    }

    protected function getGroupValueForRow($row)
    {
        if(is_callable($this->grouping)) {
            return call_user_func_array($this->grouping, [$row]);
        }
        elseif(is_string($this->grouping)) {
            return $row->{$this->grouping};
        }
        else {
            return '';
        }
    }
}
