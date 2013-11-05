<?php

class Waldviertlerhof extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Waldviertlerhof';
		$this->title_notifier = 'NEU';
		$this->address = 'Schönbrunnerstrasse 20, 1050 Wien';
		$this->addressLat = '48.193692';
		$this->addressLng = '16.358687';
		$this->url = 'http://www.waldviertlerhof.at/';
		$this->dataSource = 'http://www.waldviertlerhof.at/htm/wochenmenue.doc';
		$this->statisticsKeyword = 'waldviertlerhof';
		$this->weekendMenu = 0;
		$this->lookaheadSafe = true;

		parent::__construct();
	}

	protected function parseDataSource() {
		$dataTmp = doctotxt($this->dataSource);
		$dataTmp = preg_replace('/[[:blank:]]+/', ' ', $dataTmp);
		//var_export($dataTmp);
		//return;

		// check if current day is in menu range
		$rangeStart = strposAfter($dataTmp, 'KW');
		$rangeStop = strpos($dataTmp, date('Y', $this->timestamp), $rangeStart);
		if (!$rangeStart || !$rangeStop)
			return;
		$range_date = substr($dataTmp, $rangeStart, $rangeStop - $rangeStart);
		$range_date = trim($range_date, '. ');
		$range_date = str_replace(array(getGermanMonthName(), getGermanMonthName(1), '.'), '', $range_date);
		$range_date = explode('-', $range_date);
		if (count($range_date) != 2)
			return;
		$range_date_min = intval($range_date[0]);
		$range_date_max = intval($range_date[1]);
		$today = intval(date('j', $this->timestamp));
		if (
			($today >= $range_date_min && $today <= $range_date_max) ||
			($today <= $range_date_min && $today <= $range_date_max)
		) {
			// all fine
		}
		else
			return;

		$today = getGermanDayName();
		$tomorrow = getGermanDayName(1);
		$posStart = striposAfter($dataTmp, $today);
		if ($posStart === FALSE) {
			return;
		}
		$posEnd = stripos($dataTmp, $tomorrow, $posStart);
		// last day of the week
		if (!$posEnd)
			$posEnd = stripos($dataTmp, 'Ihre Tischreservierung', $posStart);
		$dataTmp = substr($dataTmp, $posStart, $posEnd-$posStart);
		$dataTmp = explode('|', $dataTmp);
		//var_export($dataTmp);
		//return;

		$data = null;
		foreach ($dataTmp as $food) {
			$food = cleanText($food);
			if (empty($food))
				continue;
			// price
			if (preg_match('/^[0-9]+,[0-9]+$/', $food))
				$this->price = $food;
			// food part
			else
				$data = empty($data) ? $food : "$data $food";
		}

		$this->data = $data;

		// set date
		$this->date = $today;

		//var_export($data);
		//return;
		return $this->data;
	}

	public function parseDataSource_fallback() {
	}

	public function isDataUpToDate() {
		if ($this->date == getGermanDayName())
			return true;
		else
			return false;
	}
}

?>
