<?php

class DeliciousMonster extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Delicious Monster';
		$this->address = 'Gußhausstraße 12, 1040 Wien';
		$this->addressLat = '48.1975648';
		$this->addressLng = '16.3720652';
		$this->url = 'http://www.deliciousmonster.at/';
		$this->dataSource = 'http://www.deliciousmonster.at/images/mittagsmenue-1040-wien/wochenkarte-delicious-monster-restaurant-wien.pdf';
		$this->menu = 'http://www.deliciousmonster.at/images/mittagsmenue-1040-wien/speisekarte-lokal-1040-wien-delicious-monster.pdf';
		$this->statisticsKeyword = 'deliciousmonster';
		$this->no_menu_days = array(0, 6);
		$this->lookaheadSafe = true;

		parent::__construct();
	}

	protected function get_today_variants() {
		return array();
	}

	protected function parseDataSource() {
		/*$dataTmp = file_get_contents($this->dataSource);
		if ($dataTmp === FALSE)
			return;*/
		$dataTmp = pdftohtml($this->dataSource);
		//return error_log($dataTmp);

		if (stripos($dataTmp, 'urlaub') !== false)
			return ($this->data = VenueStateSpecial::Urlaub);

		$today = date('j.n.', $this->timestamp);
		$today = mb_strtoupper(getGermanDayName()) . " $today";
		$posStart = strposAfter($dataTmp, $today);
		if ($posStart === false) {
			$today = date('j.n', $this->timestamp);
			$posStart = striposAfter($dataTmp, $today);
		}
		if ($posStart === false)
			return;
		//return error_log($posStart);
		$posEnd = mb_stripos($dataTmp, '*', $posStart);
		// last day of the week
		if ($posEnd === false)
			$posEnd = mb_stripos($dataTmp, 'Alle', $posStart);
		if ($posEnd === false)
			$posEnd = mb_stripos($dataTmp, 'Al e', $posStart);
		//return error_log($posEnd);
		$data = mb_substr($dataTmp, $posStart, $posEnd-$posStart);
		$data = strip_tags($data);
		//return error_log($data);
		// split per new line
		$foods = explode("\n", $data);

		$data = null;
		$cnt = 1;
		foreach ($foods as $food) {
			if (!empty($food)) {
				if (!$data) {
						$data = cleanText($food);
				}
				else {
					$data .= "\n$cnt. " . cleanText($food);
					$cnt++;
				}
			}
		}

		$data = mb_str_replace("\n", "<br />", $data);
		$this->data = $data;
		//return error_log($data);

		// set date
		$this->date = $today;

		// set price
		$this->price = '7,90';

		//var_export($data);

		return $this->data;
	}

	public function parseDataSource_fallback() {
	}

	public function isDataUpToDate() {
		$today = date('j.n.', $this->timestamp);
		$today = strtoupper(getGermanDayName()) . " $today";

		if ($this->date == $today)
			return true;
		else
			return false;
	}
}

?>