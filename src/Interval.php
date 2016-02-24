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

    public function __construct($step = null, Carbon $start = null, Carbon $end = null, $lang = 'en')
    {
        $this->step = $step;
        $this->start = $start;
        $this->end = $end;
        $this->lang = $lang;
    }

    public function step($step = null)
    {
        if (isset($step)) {
            $this->step = $step;
            return $this;
        } else {
            return $this->step;
        }
    }

    public function start(Carbon $date = null)
    {
        if (isset($date)) {
            $this->start = $date;
            return $this;
        } else {
            return $this->start;
        }
    }

    public function end(Carbon $date = null)
    {
        if (isset($date)) {
            $this->end = $date;
            return $this;
        } else {
            return $this->end;
        }
    }

    public function getStepIndexes()
    {
        $dates = [];

        $current = $this->start->timestamp;
        $last = $this->end->timestamp;

        while ($current <= $last) {
            $dates[] = date($this->getStepFormat(), $current);
            $current = strtotime($this->getStepIncrement(), $current);
        }

        return $dates;
    }


    /**
     * @param       $date
     *
     * @return int
     *
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