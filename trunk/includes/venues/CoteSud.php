<?php

class CoteSud extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Coté Sud';
		$this->address = 'Schleifmühlgasse 8, 1040 Wien';
		$this->addressLat = '48.196901';
		$this->addressLng = '16.365892';
		$this->url = 'http://www.cotesud.at/';
		$this->dataSource = 'http://www.cotesud.at/Menu.pdf';
		$this->statisticsKeyword = 'cotesud';
		$this->no_menu_days = array(0, 6);
		$this->lookaheadSafe = true;

		parent::__construct();
	}

/*
 * structure:
 * vom	17.	bis	21.	11	2014
 * Montag,	17.	November
 * Suppe	oder	Salat
 * 1)Kraufleckerln
 * 2)Paprikahuhn	mit	Reis
 */

	protected function parseDataSource() {
		/*$dataTmp = file_get_contents($this->dataSource);
		if ($dataTmp === FALSE)
			return;*/
		$dataTmp = pdftohtml($this->dataSource);

		if (stripos($dataTmp, 'urlaub') !== false)
			return ($this->data = VenueStateSpecial::Urlaub);

		// get validity date range
		preg_match('/vom([\s]*[\d.]*)+bis([\s]*[\d.]*)+/', $dataTmp, $matches);
		//error_log(print_r($matches[0], true));
		if (!isset($matches[0]) || empty($matches[0]))
			return;
		// cleanup by removing space, tabs, line feeds and dots
		$matches[0] = preg_replace('/[.\s]/', '', $matches[0]);
		//error_log(print_r($matches[0], true));
		// get start and end date strings
		$range       = array();
		$range[0] = mb_substr($matches[0], 0, stripos($matches[0], 'bis'));
		$range[0] = mb_substr($range[0], striposAfter($matches[0], 'vom'));
		$range[1] = mb_substr($matches[0], striposAfter($matches[0], 'bis'));
		// complete date strings
		foreach ($range as &$date_string) {
			// 17 => 17.11.2014
			if (strlen($date_string) == 2)
				$date_string .= date('.m.Y', $this->timestamp);
			// 21112014 => 21.11.2014
			else if (strlen($date_string) == 8)
				$date_string = preg_replace('/[\d]{2}[\d]{2}[\d]{4}/', '$1.$2.$3', $date_string);
		}
		unset($date_string);
		// parse date from strings
		$range[0] = strtotime($range[0]);
		$range[1] = strtotime($range[1]);
		//error_log(date('d.m.Y', $range[0]));
		//error_log(date('d.m.Y', $range[1]));
		// check if date is in range
		if ($this->timestamp >= $range[1] || $this->timestamp <= $range[0])
			return;

		// fix unclean data by replacing tabs with spaces
		$dataTmp = str_replace(array("\t", "\r"), array(' ', ' '), $dataTmp);
		// remove multiple spaces
		$dataTmp = preg_replace('/( )+/', ' ', $dataTmp);
		//return error_log($dataTmp);

		// get menu data for the chosen day
		$today = getGermanDayName() . date(', d. ', $this->timestamp) . getGermanMonthName();
		//return error_log($today);

		$posStart = strposAfter($dataTmp, $today);
		// fix pdf font bug where 'tt' turns to '#'
		if ($posStart === false)
			$posStart = strposAfter($dataTmp, str_replace('tt', '#', $today));
		if ($posStart === false)
			return;
		//return error_log($posStart);
		$posEnd = mb_stripos($dataTmp, getGermanDayName(1), $posStart);
		// last day of the week
		if ($posEnd === false)
			$posEnd = mb_stripos($dataTmp, str_replace('tt', '#', getGermanDayName(1)));
		if ($posEnd === false)
			$posEnd = mb_stripos($dataTmp, 'Reservierung', $posStart);
		if ($posEnd === false)
			return;
		//return error_log($posEnd);

		$data = mb_substr($dataTmp, $posStart, $posEnd-$posStart);
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
		$cnt = 1;
		foreach ($foods as $food) {
			$food = cleanText($food);
			if (!empty($food)) {
				if (!$data) {
					// starter
					$data = trim($food, '+ ');
				}
				else {
					// new food
					if (preg_match("/[0-9]+\)/", $food)) {
						// remove pdf numbering via regex
						$data .= "\n${cnt}. " . preg_replace('/[0-9]+\)/', '', $food);
						$cnt++;
					}
					// staff from before in new line only
					else
						$data .= ' ' . $food;
				}
			}
		}
		$data = str_replace("\n", "<br />", $data);
		$this->data = $data;

		// set date
		$this->date = $today;

		// set price
		$this->price = array('6,90', '9,30');

		return $this->data;
	}

	public function parseDataSource_fallback() {
	}

	public function isDataUpToDate() {
		//return false;
		$today = getGermanDayName() . date(', d. ', $this->timestamp) . getGermanMonthName();

		if ($this->date == $today)
			return true;
		else {
			// fix pdf font bug where 'tt' turns to '#'
			$today = str_replace('tt', '#', $today);
			if ($this->date == $today)
				return true;
		}
		return false;
	}
}

?>
