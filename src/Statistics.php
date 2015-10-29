<?php
namespace WhiteFrame\Statistics;

/**
 * Class Statistics
 */
class Statistics
{
	protected $query;

	protected $startDate;
	protected $endDate;
	protected $step;

	protected $indicators;
	protected $grouping;

	protected $cache;

	public static $COUNTER = 1;
	public static $VALUE = 2;

	/**
	 * @param $query
	 */
	public function __construct($query)
	{
		$this->query = $query;
		$this->indicators = [];
		
		$this->step = 'daily';
		
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
	 * @param $startDate
	 * @param $endDate
	 *
	 * @return $this
	 */
	public function setInterval($startDate, $endDate)
	{
		$this->cache = null;

		// Handle date formatting
		$this->startDate = preg_replace("#([0-9]{2})+\/([0-9]{2})+\/([0-9]{4})+#", "$3-$2-$1", $startDate);
		$this->endDate = preg_replace("#([0-9]{2})+\/([0-9]{2})+\/([0-9]{4})+#", "$3-$2-$1", $endDate);

		return $this;
	}

	/**
	 * @return array
	 */
	public function getInterval()
	{
		return [
			'start_date' => $this->startDate,
			'end_date' => $this->endDate
		];
	}

	/**
	 * @param $stepName
	 *
	 * @return $this
	 */
	public function setStep($step)
	{
		$this->cache = null;
		$this->step = $step;

		return $this;
	}

	/**
	 * @param $name
	 * @param $method
	 *
	 * @return $this
	 */
	public function addIndicator($name, $function, $method = 1)
	{
		$this->cache = null;
		$this->indicators[$name] = [
			'method' => $method,
			'function' => $function
		];

		return $this;
	}

	/**
	 * @param $column
	 *
	 * @return $this
	 */
	public function setGrouping($column)
	{
		$this->cache = null;
		$this->grouping = $column;

		return $this;
	}

	/**
	 * @return array
	 */
	public function make()
	{
		if(!is_null($this->cache))
			return $this->cache;

		$datas = [];
		$statistics = [
			'indicators' => [

			],
			'collection' => [
				'count' => 0
			]
		];

		// Set interval
		$this->query->whereBetween('created_at', [$this->startDate . " 00:00:00", $this->endDate . " 23:59:59"]);

		// Filling datas with empty values on all range dates
		foreach($this->getStepIndexes() as $index) {
			$datas[$index] = $this->getEmptyDatas();
		}

		// Building datas with indicators functions
		foreach($this->query->get() as $row) {
			$index = $this->getDateToStepIndex($row->created_at);
			$datas[$index] = $this->getDatasForRow($row, $datas[$index]);

			// Statistics
			$statistics['collection']['count']++;
		}

		$statistics['indicators'] = $this->getIndicatorStatistics($datas);

		return $this->cache = [
			'datas' => $datas,
			'statistics' => $statistics,
			'interval' => [
				'start_date' => $this->startDate,
				'end_date' => $this->endDate
			]
		];
	}

	/**
	 * @return array
	 */
	public function getEmptyDatas()
	{
		$datas = [];

		foreach($this->indicators as $name => $infos) {
			if($infos['method'] == self::$COUNTER) $datas[$name] = 0;
			elseif($infos['method'] == self::$VALUE) $datas[$name] = null;
		}

		return $datas;
	}

	/**
	 * @param $row
	 *
	 * @return array
	 */
	public function getDatasForRow($row, $old)
	{
		$datas = [];

		foreach($this->indicators as $name => $infos) {
			$oldValue = $old[$name];
			$newValue = $infos['function']($row);

			if($infos['method'] == self::$COUNTER) $value = $oldValue + $newValue;
			elseif($infos['method'] == self::$VALUE) $value[] = $newValue;

			$datas[$name] = $value;
		}

		return $datas;
	}

	/**
	 * @param $datas
	 *
	 * @return array
	 */
	public function getIndicatorStatistics($datas)
	{
		$statistics = [
			'sum' => [],
			'min' => [],
			'max' => [],
			'avg' => []
		];

		$tempAvg = [];

		// Getting datas for indicators statistics
		foreach($datas as $index => $indicators) {
			foreach($indicators as $indicator => $value) {

				// SUM
				if(!isset($statistics['sum'][$indicator])) {
					$statistics['sum'][$indicator] = 0;
				}
				$statistics['sum'][$indicator] += $value;

				// MIN
				if(!isset($statistics['min'][$indicator]) OR $value < $statistics['min'][$indicator]) {
					$statistics['min'][$indicator] = $value;
				}

				// MAX
				if(!isset($statistics['max'][$indicator]) OR $value > $statistics['max'][$indicator]) {
					$statistics['max'][$indicator] = $value;
				}

				// AVG
				if(!isset($tempAvg[$indicator])) {
					$tempAvg[$indicator] = [];
				}
				$tempAvg[$indicator][] = $value;
			}
		}

		// Making average values
		foreach($tempAvg as $indicator => $values) {
			$sum = 0;
			foreach($values as $value) {
				$sum += $value;
			}

			if($sum == 0) $statistics['avg'][$indicator] = null;
			else $statistics['avg'][$indicator] = $sum / count($values);
		}

		return $statistics;
	}

	/**
	 * @return array
	 *
	 */
	public function getStepIndexes()
	{

		$dates = [];
		$current = strtotime($this->startDate);
		$last = strtotime($this->endDate);

		while($current <= $last) {

			$dates[] = date($this->getStepInfos()['format'], $current);
			$current = strtotime($this->getStepInfos()['step'], $current);
		}

		return $dates;
	}


	/**
	 * @param       $date
	 *
	 * @return int
	 *
	 */
	public function getDateToStepIndex($date)
	{
		$time = strtotime($date);
		$parsed = getdate($time);

		switch($this->step) {
			case "yearly":
				$parsed["mon"] = 0;
			case "monthly":
				$parsed["mday"] = 0;
			case "daily":
				$parsed["hours"] = 0;
			case "hourly":
				$parsed["minutes"] = 0;
			default:
				$parsed["seconds"] = 0;
				break;
		}

		$newTime = mktime(
			$parsed["hours"],
			$parsed["minutes"],
			$parsed["seconds"],
			$parsed["mon"],
			$parsed["mday"],
			$parsed["year"]
		);

		return date($this->getStepInfos()['format'], $newTime);
	}

	/**
	 * @return mixed
	 */
	public function getStep()
	{
		return $this->step;
	}

	/**
	 * @return array
	 */
	public function getStepInfos()
	{
		switch($this->step) {
			case "yearly":
				return [
					'step' => '+1 year',
					'format' => 'Y'
				];
				break;

			case "monthly":
				return [
					'step' => '+1 month',
					'format' => 'm/Y'
				];
				break;

			case "daily":
				return [
					'step' => '+1 day',
					'format' => 'd/m/Y'
				];
				break;

			case "hourly":
				return [
					'step' => '+1 hour',
					'format' => 'd/m/Y h:00'
				];
				break;
		}
	}

	/**
	 * @return Highcharts
	 */
	public function getHighcharts()
	{
		return new Highcharts($this);
	}
}
