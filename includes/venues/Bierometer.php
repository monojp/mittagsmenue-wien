<?php

class Bierometer extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Bierometer';
		//$this->title_notifier = 'UPDATE';
		$this->address = 'Margaretenplatz 9, 1050 Wien';
		$this->addressLat = '48.192031';
		$this->addressLng = '16.358819';
		$this->url = 'http://www.bierometer-2.at/';
		$this->dataSource = 'http://www.bierometer-2.at/menueplan/';
		$this->statisticsKeyword = 'bierometer';
		$this->no_menu_days = array(0, 6);
		$this->lookaheadSafe = true;
		$this->price_nested_info = 'ohne Suppe / mit Suppe';

		parent::__construct();
	}

	private function parse_helper($data, $soup_output = true, &$food_counter) {
		//return error_log($data);
		$data = strip_tags($data, '<br>');
		// remove unwanted stuff
		$data = str_replace(array('&nbsp;'), '', $data);
		$data = str_ireplace(array("<br />","<br>","<br/>"), "\r\n", $data);
		// remove multiple newlines
		$data = preg_replace("/(\n)+/i", "\n", $data);
		$data = trim($data);
		// split per new line
		$foods = explode("\n", $data);
		//return error_log(print_r($foods, true));

		$data = null;
		$food_counter = 1;
		foreach ($foods as $food) {
			$food = preg_replace('/Menü [1-9]+/', '', $food);
			$food = cleanText($food);
			if (!empty($food)) {
				// data empty, soup
				if ($soup_output && empty($data)) {
					$data = "$food\n";
				}
				// default menu
				else {
					$data .= "$food_counter. $food\n";
					$food_counter++;
				}
			}
		}
		$data = trim($data);
		return str_replace("\n", "<br />", $data);
	}

	// returns menu_data as string or null if nothing found
	private function parse_single($dataTmp, $min_food_count = 2) {
		$today = date('d.m.', $this->timestamp);
		$posStart = striposAfter($dataTmp, $today, striposAfter($dataTmp, '</h3>'));
		//$posStart = 10;
		if ($posStart === false)
			return null;
		$weekday = date('w', $this->timestamp);
		if ($weekday == 5) // friday
			$posEnd = mb_stripos($dataTmp, 'table', $posStart);
		else
			$posEnd = mb_stripos($dataTmp, date('d.m.', strtotime('+1 day', $this->timestamp)), $posStart);
		$data = mb_substr($dataTmp, $posStart, $posEnd-$posStart);

		$data = $this->parse_helper($data, true, $food_counter);
		if ($food_counter < $min_food_count)
			return null;
		//return error_log($data);
		return $data;
	}

	function parse_multi($dataTmp) {
		$posStart = mb_strpos($dataTmp, 'Menü 1');
		$posEnd   = mb_strpos($dataTmp, 'Menü 10', $posStart);
		if ($posEnd !== false)
			$posEnd   = mb_strpos($dataTmp, 'table', $posEnd);

		// get all 10 foods
		if ($posStart === false || $posEnd === false)
			return null;
		$data = mb_substr($dataTmp, $posStart, $posEnd-$posStart);

		$data = $this->parse_helper($data, false, $food_counter);
		if ($data === null)
			return null;

		// get soup of the day with single parser
		$soup = $this->parse_single($dataTmp, 1);
		if ($soup === null)
			return null;

		return "$soup<br />$data";
	}

	protected function parseDataSource() {
		$dataTmp = file_get_contents($this->dataSource);
		if ($dataTmp === FALSE)
			return;
		//return error_log($dataTmp);

		//if (stripos($dataTmp, 'urlaub') !== false)
		//	return ($this->data = VenueStateSpecial::Urlaub);

		// check if current day is in data range
		preg_match('/Menüplan [0-9]{2}.[0-9]{2}.[ ]+-[ ]+[0-9]{2}.[0-9]{2}.[0-9]{4}/', $dataTmp, $matches);
		if (!isset($matches[0]) || empty($matches[0]))
			return;
		$matches = str_replace('Menüplan', '', $matches[0]);
		$matches = explode('-', $matches);
		if (!isset($matches[0]) || !isset($matches[1]))
			return;
		$date_start = trim($matches[0]);
		$date_end   = trim($matches[1]);
		// complete date_start with missing year from date_end
		$date_start .= explode('.', $date_end)[2];
		// parse dates to timestamps
		$date_start = strtotime($date_start);
		$date_end   = strtotime($date_end);
		$date_end   = strtotime('+1 day', $date_end); // php uses current day 00:00:00, fix range check by +1 day
		//error_log($this->timestamp);
		//error_log($date_start);
		//error_log($date_end);
		//error_log(date('r', $this->timestamp));
		//error_log(date('r', $date_start));
		//error_log(date('r', $date_end));
		if (($date_start > $this->timestamp || $date_end < $this->timestamp) && strpos($dataTmp, date('d.m.', $this->timestamp)) === false)
			return;

		// multi parser
		$data = $this->parse_multi($dataTmp);
		if ($data === null) {
			// default single parser
			$data = $this->parse_single($dataTmp);
			if ($data === null)
				return;
		}

		// set data
		$this->data = $data;

		// set date
		$this->date = date('d.m.', $this->timestamp);

		// set price
		$this->price = array(array('5.8'));

		return $this->data;
	}

	public function parseDataSource_fallback() {
	}

	public function isDataUpToDate() {
		if ($this->date == date('d.m.', $this->timestamp))
			return true;
		else
			return false;
	}
}

?>