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
		$today_variants[] = strtoupper(getGermanDayName()) . ' ' . date('j.n.', $this->timestamp);
		$today_variants[] = strtoupper(getGermanDayName()) . '!' . date('j.n.', $this->timestamp);
		$today_variants[] = strtoupper(getGermanDayName()) . '&#160;' . date('j.n.', $this->timestamp);
		return $today_variants;
	}

	protected function parseDataSource() {
		/*$dataTmp = file_get_contents($this->dataSource);
		if ($dataTmp === FALSE)
			return;*/
		$dataTmp = pdftohtml($this->dataSource);
		//return error_log($dataTmp);

		if (stripos($dataTmp, 'urlaub') !== false)
			return ($this->data = VenueStateSpecial::Urlaub);

		// get menu data for the chosen day
		$today_variants = $this->get_today_variants();
		//return error_log(print_r($today, true));

		$today = null;
		foreach ($today_variants as $today) {
			$posStart = strposAfter($dataTmp, $today);
			if ($posStart !== false)
				break;
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
}
