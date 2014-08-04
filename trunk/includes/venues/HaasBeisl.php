<?php

class HaasBeisl extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Haas Beisl';
		$this->address = 'Margaretenstraße 74, 1050 Wien';
		$this->addressLat = '48.1925285';
		$this->addressLng = '16.3602763';
		$this->url = 'http://www.haasbeisl.at/';
		$this->dataSource = 'http://www.haasbeisl.at/pdf/aktuelles.pdf';
		$this->statisticsKeyword = 'haasbeisl';
		$this->no_menu_days = array(0, 6);
		$this->lookaheadSafe = true;

		parent::__construct();
	}

	protected function parseDataSource() {
		/*$dataTmp = file_get_contents($this->dataSource);
		if ($dataTmp === FALSE)
			return;*/
		$dataTmp = pdftohtml($this->dataSource);

		if (stripos($dataTmp, 'urlaub') !== false)
			return ($this->data = VenueStateSpecial::Urlaub);

		$month = getGermanMonthName();
		if ($month == 'Februar')
			$month = 'Feber';

		// date without trailing 0 and without .
		$today = getGermanDayName() . ', ' . date('j', $this->timestamp) . ' ' . $month;
		$posStart = strposAfter($dataTmp, $today);
		// date with trailing 0 and without .
		if ($posStart === false) {
			$today = getGermanDayName() . ', ' . date('d', $this->timestamp) . ' ' . $month;
			$posStart = strposAfter($dataTmp, $today);
			// date with trailing 0 and with .
			if ($posStart === false) {
				$today = getGermanDayName() . ', ' . date('d.', $this->timestamp) . ' ' . $month;
				$posStart = strposAfter($dataTmp, $today);
				// date with trailing 0 and with .
				if ($posStart === false) {
					$today = getGermanDayName() . ', ' . date('j.', $this->timestamp) . ' ' . $month;
					$posStart = strposAfter($dataTmp, $today);
					if ($posStart === false)
						return;
				}
			}
		}
		$friday = (date('w', $this->timestamp) == 5);
		if (!$friday)
			$posEnd = mb_stripos($dataTmp, getGermanDayName(1), $posStart);
		else
			$posEnd = mb_strpos($dataTmp, 'Menüsalat', $posStart);
		$data = mb_substr($dataTmp, $posStart, $posEnd-$posStart);
		//$data = html_entity_decode($data);
		$data = strip_tags($data, '<br>');
		// remove unwanted stuff
		$data = str_replace(array('&nbsp;'), '', $data);
		$data = str_ireplace(array("<br />","<br>","<br/>"), "\r\n", $data);
		$data = preg_replace("/([a-z])\n([a-z])/i", '$1 $2', $data);
		// remove multiple newlines
		$data = preg_replace("/(\n)+/i", "\n", $data);
		// remove "1.\n" dirty data
		$data = preg_replace("/([1-9].)(\n)+/", "$1", $data);
		$data = trim($data);
		//return error_log($data);
		// split per new line
		$foods = explode("\n", $data);
		//return error_log(print_r($foods, true));

		$data = null;
		foreach ($foods as $food) {
			$food = cleanText($food);
			if (!empty($food)) {
				if (!$data) {
					// starter
					$data = $food;
				}
				else {
					// checker for empty entries in pdf
					$foodWithoutNumber = cleanText(preg_replace("/[0-9]{1,1}\./", '', $food));
					if (empty($foodWithoutNumber))
						break;
					// found new food
					if (preg_match("/[0-9]{1,1}/", $food)) {
						$data .= "\n" . $food;
					}
					// found staff from before in new line only
					else {
						$data .= ' ' . $food;
					}
				}
			}
		}
		//var_export($data);
		//return;
		$data = str_replace("\n", "<br />", $data);
		$this->data = $data;

		// set date
		$this->date = $today;

		// set price
		$this->price = array('7,90', '6,10');

		//var_export($data);
		return $this->data;
	}

	public function parseDataSource_fallback() {
	}

	public function isDataUpToDate() {
		//return false;
		$month = getGermanMonthName();
		if ($month == 'Februar')
			$month = 'Feber';
		$today = getGermanDayName() . ', ' . date('j', $this->timestamp) . ' ' . $month;

		// remove 0 and . from date to match with all 4 types from above
		$today = str_replace(array('.', '0', 0), '', $today);
		$date_compare = str_replace(array('.', '0', 0), '', $this->date);

		if ($date_compare == $today)
			return true;
		else
			return false;
	}
}

?>