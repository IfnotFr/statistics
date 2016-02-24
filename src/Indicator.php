<?php namespace WhiteFrame\Statistics;

/**
 * Class Indicator
 * @package WhiteFrame\Statistics
 */
class Indicator
{
    public static $COUNTER = 'counter';
    public static $VALUE = 'value';

    protected $name;
    protected $function;
    protected $counting_method;

    public function __construct($name, $function, $counting_method)
    {
        $this->name = $name;
        $this->function = $function;
        $this->counting_method = $counting_method;
    }

    public function getDefaultValue()
    {
        switch($this->counting_method) {
            case self::$COUNTER:
                return 0;
            break;

            case self::$VALUE:
                return null;
                break;

            default:
                throw new InvalidCountingMethodException();
        }
    }

    public function getValue($row, $oldValue)
    {
        $value = call_user_func_array($this->function, [$row]);

        switch($this->counting_method) {

            // If it is a counter add +1
            case self::$COUNTER:
                return $oldValue + $value;
                break;

            // If it is a value, add the value to the set
            case self::$VALUE:
                $oldValue[] = $value;
                return $oldValue;
                break;

            default:
                throw new InvalidCountingMethodException();
        }
    }

    public function buildValue($value)
    {
        if($this->counting_method == Indicator::$VALUE) {
            return array_sum($value) / count($value);
        }
        else {
            return $value;
        }
    }
}