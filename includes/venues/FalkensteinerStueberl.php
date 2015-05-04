<?php

class FalkensteinerStueberl extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Falkensteiner Stüberl';
		$this->title_notifier = 'NEU';
		$this->address = 'Kleistgasse 28, 1030 Wien';
		$this->addressLat = '48.189105';
		$this->addressLng = '16.388750';
		$this->url = 'http://www.falkensteinerstueberl.at/';
		$this->dataSource = 'http://www.falkensteinerstueberl.at/menueplankleistgasse.pdf';
		$this->menu = 'http://www.falkensteinerstueberl.at/html/speisekarte.htm';
		$this->statisticsKeyword = 'falkensteinerstueberl';
		$this->no_menu_days = array();
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
		$today_variants[] = getGermanDayName();
		return $today_variants;
	}

	protected function parseDataSource() {
		/*$dataTmp = file_get_contents($this->dataSource);
		if ($dataTmp === FALSE)
			return;*/
		$dataTmp = pdftotext($this->dataSource);
		if (stripos($dataTmp, 'urlaub') !== false)
			return ($this->data = VenueStateSpecial::Urlaub);
		//return error_log($dataTmp);

		// check date range
		if (!$this->in_date_range_string($dataTmp, $this->timestamp, '%d.%m.', '%d.%m.')) {
			return;
		}

		// check menu food count
		if ($this->get_holiday_count($dataTmp) + $this->get_soup_count($dataTmp) != 7) {
			return;
		}

		// remove unwanted stuff
		$data = $dataTmp;
		//$data = preg_replace("/([a-z])\n([a-z])/i", '$1 $2', $data);
		// remove multiple newlines
		$data = preg_replace("/(\n)+/i", "\n", $data);
		$data = trim($data);
		//return error_log($data);
		// split per new line
		$foods = explode("\n", $data);
		//return error_log(print_r($foods, true));

		$data = $this->parse_foods_independant_from_days($foods, "\n", $prices, false);
		//return error_log(print_r($prices, true));
		//return error_log($data);

		$this->data = $data;
		$this->price = $prices;

		// set date
		$this->date = reset($this->get_today_variants());

		return $this->data;
	}
}
