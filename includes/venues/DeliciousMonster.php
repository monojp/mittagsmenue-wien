<?php

class DeliciousMonster extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Delicious Monster';
		$this->address = 'Gußhausstraße 12, 1040 Wien';
		$this->addressLat = 48.1975648;
		$this->addressLng = 16.3720652;
		$this->url = 'http://www.deliciousmonster.at/';
		$this->dataSource = 'http://www.deliciousmonster.at/images/mittagsmenue-1040-wien/wochenkarte-delicious-monster-restaurant-wien.pdf';
		$this->menu = 'http://www.deliciousmonster.at/images/mittagsmenue-1040-wien/speisekarte-lokal-1040-wien-delicious-monster.pdf';
		$this->statisticsKeyword = 'deliciousmonster';
		$this->no_menu_days = [ 0, 6 ];
		$this->lookaheadSafe = true;

		parent::__construct();
	}

	protected function get_today_variants() {
		return [
			strtoupper(getGermanDayName()) . ' ' . date('j.n.', $this->timestamp),
			strtoupper(getGermanDayName()) . '!' . date('j.n.', $this->timestamp),
			strtoupper(getGermanDayName()) . '&#160;' . date('j.n.', $this->timestamp),
		];
	}

	protected function parseDataSource() {
		$dataTmp = pdftohtml($this->dataSource);
		if (!$dataTmp) {
			return;
		}
		//return error_log($dataTmp);

		$dataTmp = html_clean($dataTmp);
		//return error_log($dataTmp);

		$data = $this->parse_foods_inbetween_days($dataTmp, '*', [ 'Alle', 'Al e' ]);
		if (!$data || is_numeric($data)) {
			return $this->data = $data;
		}
		//return error_log($data);

		// set price
		$this->price = $this->parse_prices_regex($dataTmp, [ '/\d{1},\d{2}/' ]);
		//return error_log(print_r($this->price, true));

		return ($this->data = $data);
	}

}
