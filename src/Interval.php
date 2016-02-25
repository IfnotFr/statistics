<?php namespace WhiteFrame\Statistics;

use Carbon\Carbon;

/**
 * Class Interval
 * @package WhiteFrame\Statistics
 */
class Interval
{
    public static $HOURLY = 'hourly';
    public static $DAILY = 'daily';
    public static $MONTHLY = 'monthly';
    public static $YEARLY = 'yearly';

    protected $step;
    protected $start;
    protected $end;
    protected $lang;

    /**
     * Interval constructor.
     * Create a new interval by specifying duration, steps and lang
     *
     * @param null $step
     * @param Carbon|null $start
     * @param Carbon|null $end
     * @param string $lang
     */
    public function __construct($step = null, Carbon $start = null, Carbon $end = null, $lang = 'en')
    {
        $this->step = $step;
        $this->start = $start;
        $this->end = $end;
        $this->lang = $lang;
    }

    /**
     * Set or get the step of the interval
     *
     * @param null $step
     * @return $this|null
     */
    public function step($step = null)
    {
        if (isset($step)) {
            $this->step = $step;
            return $this;
        } else {
            return $this->step;
        }
    }

    /**
     * Set or get the start date of the interval
     *
     * @param Carbon|null $date
     * @return $this|Carbon
     */
    public function start(Carbon $date = null)
    {
        if (isset($date)) {
            $date->hour = 0;
            $date->minute = 0;
            $date->second = 0;

            $this->start = $date;

            return $this;
        } else {
            return $this->start;
        }
    }

    /**
     * Set or get the end date of the interval
     *
     * @param Carbon|null $date
     * @return $this|Carbon
     */
    public function end(Carbon $date = null)
    {
        if (isset($date)) {
            $date->hour = 0;
            $date->minute = 0;
            $date->second = 0;

            $this->end = $date;

            return $this;
        } else {
            return $this->end;
        }
    }

    /**
     * Get an array containing the dates (with step precision) beteween the start and the end date
     *
     * @return array
     */
    public function getStepIndexes()
    {
        $dates = [];

        // TODO : Think about doing this a cleaner way with carbon instead with timestamps ...
        $current = $this->start->timestamp;
        $last = $this->end->timestamp;

        while ($current <= $last) {
            $dates[] = date($this->getStepFormat(), $current);
            $current = strtotime($this->getStepIncrement(), $current);
        }

        return $dates;
    }

    /**
     * Converting a date to his step index according to the step precision of the interval
     *
     * @param Carbon $date
     * @return string
     */
    public function getStepIndexFromDate(Carbon $date)
    {
        switch ($this->step) {
            case "yearly":
                $date->month = 0;
            case "monthly":
                $date->day = 0;
            case "daily":
                $date->hour = 0;
            case "hourly":
                $date->minute = 0;
            default:
                $date->second = 0;
                break;
        }

        return $date->format($this->getStepFormat());
    }

    /**
     * Get the step index format of the step precision
     *
     * @return array
     */
    public function getStepFormat()
    {
        if ($this->lang == 'en') {
            switch ($this->step) {
                case self::$YEARLY:
                    return 'Y';
                    break;
                case self::$MONTHLY:
                    return 'Y-m';
                    break;
                case self::$DAILY:
                    return 'Y-m-d';
                    break;
                case self::$HOURLY:
                    return 'Y-m-d H:00';
                    break;
            }
        } elseif ($this->lang == 'fr') {
            switch ($this->step) {
                case self::$YEARLY:
                    return 'Y';
                    break;
                case self::$MONTHLY:
                    return 'm/Y';
                    break;
                case self::$DAILY:
                    return 'd/m/Y';
                    break;
                case self::$HOURLY:
                    return 'd/m/Y H:00';
                    break;
            }
        }

        return false;
    }

    /**
     * Get the string corresponding to the incrementation of each steps
     *
     * @return string
     */
    public function getStepIncrement()
    {
        switch ($this->step) {
            case self::$YEARLY:
                return '+1 year';
                break;
            case self::$MONTHLY:
                return '+1 month';
                break;
            case self::$DAILY:
                return '+1 day';
                break;
            case self::$HOURLY:
                return '+1 hour';
                break;
        }
    }
}