<?php

class HaasBeisl extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Haas Beisl';
		$this->address = 'Margaretenstraße 74, 1050 Wien';
		$this->addressLat = 48.1925285;
		$this->addressLng = 16.3602763;
		$this->url = 'http://www.haasbeisl.at/';
		$this->dataSource = 'http://www.haasbeisl.at/pdf/aktuelles.pdf';
		$this->menu = 'http://www.haasbeisl.at/pdf/tageskarte.pdf';
		$this->no_menu_days = [ 0, 6 ];
		$this->lookaheadSafe = true;

		parent::__construct();
	}

	protected function get_today_variants() {
		$month = getGermanMonthName();

		$today_variants[] = getGermanDayName() . ', ' . date('j', $this->timestamp)  . ' ' . $month;
		$today_variants[] = getGermanDayName() . ', ' . date('d', $this->timestamp)  . ' ' . $month;
		$today_variants[] = getGermanDayName() . ', ' . date('d.', $this->timestamp) . ' ' . $month;
		$today_variants[] = getGermanDayName() . ', ' . date('j.', $this->timestamp) . ' ' . $month;
		$today_variants[] = getGermanDayName() . ', ' . date('j', $this->timestamp) . $month;
		$today_variants[] = getGermanDayName() . ', ' . date('d', $this->timestamp) . $month;
		$today_variants[] = getGermanDayName() . ', ' . date('d.', $this->timestamp) . $month;
		$today_variants[] = getGermanDayName() . ', ' . date('j.', $this->timestamp) . $month;

		if ($month == 'Februar') {
			for ($i = 0; $i < count($today_variants); $i++) {
				if (mb_strpos($today_variants[$i], 'Februar') !== false) {
					$today_variants[] = str_replace('Februar', 'Feber', $today_variants[$i]);
				}
			}
		}

		return $today_variants;
	}

	protected function parseDataSource() {
		$dataTmp = pdftohtml($this->dataSource);
		if (!$dataTmp)
			return;
		//return error_log($dataTmp);

		$dataTmp = html_clean($dataTmp);

		$data = $this->parse_foods_inbetween_days($dataTmp, getGermanDayName(1) . ',' ,
				[ 'Menüsalat', 'Geschlossen' ], "\n", false);
		//return error_log($data);
		if (!$data || is_numeric($data)) {
			return ($this->data = $data);
		}

		// set price
		$this->price = $this->parse_prices_regex($dataTmp, [ '/€.*/' ]);
		//return error_log(print_r($this->price, true));

		return ($this->data = $data);
	}

}
