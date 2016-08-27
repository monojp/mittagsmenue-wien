<?php

class FalkensteinerStueberl extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Falkensteiner Stüberl';
		//$this->title_notifier = 'NEU';
		$this->address = 'Kleistgasse 28, 1030 Wien';
		$this->addressLat = 48.189105;
		$this->addressLng = 16.388750;
		$this->url = 'http://www.falkensteinerstueberl.at/';
		$this->dataSource = 'http://www.falkensteinerstueberl.at/menueplankleistgasse.pdf';
		$this->menu = 'http://www.falkensteinerstueberl.at/html/speisekarte.htm';
		$this->no_menu_days = [];
		$this->lookaheadSafe = true;

		parent::__construct();
	}

/*
 * Menüplan von 27.04.2015 - 03.05.2015
 * Karottencremesuppe A,G,L
 *
 * 1.Mexicokotlette mit Bratkartoffeln
 *
 * € 7,10
 *
 * 2.Mozzarella mit Tomaten, Basilikum und Toastbrot A,G
 *
 * € 6,00
 */

	protected function get_today_variants() {
		return [
			getGermanDayName(),
		];
	}

	protected function parseDataSource() {
		//$dataTmp = pdftohtml($this->dataSource);
		$dataTmp = pdftotext($this->dataSource);
		if (!$dataTmp) {
			return;
		}
		//return error_log($dataTmp);

		//$dataTmp = html_clean($dataTmp);

		// check date range
		if (!$this->in_date_range_string($dataTmp, $this->timestamp, '%d.%m.', '%d.%m.')) {
			return;
		}

		// check menu food count (UPDATE: because of inline holiday message >= 7 should be valid)
		if ($this->get_holiday_count($dataTmp) + $this->get_starter_count($dataTmp) < 7) {
			return;
		}

		$dataTmp = $this->parse_foods_independant_from_days($dataTmp, "\n", $this->price, false);
		//return error_log(print_r($this->price, true));
		//return error_log($dataTmp);

		return ($this->data = $dataTmp);
	}

}
