<?php

class tewa extends FoodGetterVenue {

	function __construct() {
		$this->title = 'tewa Naschmarkt';
		$this->title_notifier = 'NEU';
		$this->address = 'Naschmarkt 672, 1040 Wien';
		$this->addressLat = '48.199469';
		$this->addressLng = '16.364790';
		$this->url = 'http://tewa-naschmarkt.at/';
		$this->dataSource = 'http://tewa-naschmarkt.at/tagesteller/';
		$this->statisticsKeyword = 'tewa-naschmarkt';
		$this->weekendMenu = 0;
		$this->lookaheadSafe = true;

		parent::__construct();
	}

	protected function parseDataSource() {
		$dataTmp = file_get_contents($this->dataSource);
		if ($dataTmp === FALSE)
			return;
		//error_log(print_r($dataTmp, true));
		//return;
		// remove unwanted stuff (fix broken htmlentities)
		$dataTmp = html_entity_decode($dataTmp);
		$dataTmp = htmlentities($dataTmp);
		$dataTmp = str_replace(array('&nbsp;'), ' ', $dataTmp);
		$dataTmp = html_entity_decode($dataTmp);

		$posStart = striposAfter($dataTmp, getGermanDayName());
		if ($posStart === FALSE)
			return;
		$posEnd = stripos($dataTmp, getGermanDayName(1), $posStart);
		// friday
		if (!$posEnd) {
			$posEnd = stripos($dataTmp, '</tr>', $posStart);
			if (!$posEnd)
				return;
		}
		$data = substr($dataTmp, $posStart, $posEnd - $posStart);
		$data = strip_tags($data, '<br>');
		//error_log(print_r($data, true));
		//return;
		// remove unwanted stuff
		$data = str_replace(array('&nbsp;'), '', $data);
		$data = str_ireplace(array("<br />","<br>","<br/>"), "\r\n", $data);
		$data = preg_replace("/([a-z])\n([a-z])/i", '$1 $2', $data);
		// remove multiple newlines
		$data = preg_replace("/(\n)+/i", "\n", $data);
		$data = trim($data);
		//error_log(print_r($data, true));
		//return;
		// split per new line
		$foods = explode("\n", $data);

		$data = null;
		$cnt = 0;
		foreach ($foods as $food) {
			$food = cleanText($food);
			if (!empty($food)) {
				// new found (everything uppercase)
				if (mb_strtoupper($food) == $food) {
					$cnt++;
					$data .= "\n$cnt. $food";
				}
				else
					$data .= ", $food";
			}
		}
		$data = cleanText($data);
		//error_log($data);
		//return;

		// get prices out of data
		$data_price = str_replace(',', '.', $data);
		$price = null;
		preg_match_all('/[0-9]{1,2}[.][0-9]{1,2}/', $data_price, $price);
		$this->price = $price[0];
		//error_log(print_r($price[0], true));
		//return;

		// clean data from prices
		$data = preg_replace('/[0-9]{1,2}[,][0-9]{1,2}/', '', $data);
		$this->data = $data;
		//error_log($data);
		//return;

		// set date
		$this->date = getGermanDayName();

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
