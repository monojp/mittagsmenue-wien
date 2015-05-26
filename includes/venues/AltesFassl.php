<?php

class AltesFassl extends FoodGetterVenue {

	function __construct() {
		$this->title             = 'Zum Alten Fassl';
		$this->address           = 'Ziegelofengasse 37, 1050 Wien';
		$this->addressLat        = 48.191498;
		$this->addressLng        = 16.3604868;
		$this->url               = 'http://www.zum-alten-fassl.at/';
		$this->dataSource        = 'http://www.zum-alten-fassl.at/mittagsmenues.html';
		$this->menu              = 'http://www.zum-alten-fassl.at/standard-karte.html';
		$this->statisticsKeyword = 'zum-alten-fassl';
		$this->no_menu_days      = [ 0, 6 ];
		$this->lookaheadSafe     = true;

		parent::__construct();
	}

/*
 * structure
 * Freitag 22.05.
 *
 * Tagessuppe oder Dessert: Topfenknödel in Butterbrösel
 * mit Beerenragout
 * ***
 * 1) Bratwurst mit Sauerkraut und Rösti
 * 2) Gebackenes Seelachsfilet mit Sauce Tartare und Erdäpfelsalat
 * 3) Spaghetti mit Basilikumpesto und Paradeiswürfel
 */

	protected function get_today_variants() {
		return [
			date('d.m.', $this->timestamp),
			date('d.m', $this->timestamp),
		];
	}

	protected function parseDataSource() {
		$dataTmp = html_get_clean($this->dataSource);
		if (!$dataTmp)
			return;
		//return error_log($dataTmp);

		// get menu data for the chosen day
		$data = $this->parse_foods_inbetween_days($dataTmp, getGermanDayName(1), 'MENÜ 1');
		if (!$data || is_numeric($data))
			return $this->data = $data;
		//return error_log($data);

		// set price
		$this->price = $this->parse_prices_regex($dataTmp, [ '/\d{1},\d{2}/' ]);
		//return error_log(print_r($this->price, true));

		return ($this->data = $data);
	}

}
