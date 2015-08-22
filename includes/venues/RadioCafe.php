<?php

class RadioCafe extends FoodGetterVenue {

	function __construct() {
		$this->title = 'RadioCafe';
		//$this->title_notifier = 'NEU';
		$this->address = 'Argentinierstraße 30a, 1040 Wien';
		$this->addressLat = 48.194522;
		$this->addressLng = 16.373029;
		$this->url = 'http://www.kulturcafe.eu/index.php?home';
		$this->dataSource = 'http://www.kulturcafe.eu/index.php?wochenkarte';
		$this->menu = 'http://www.kulturcafe.eu/index.php?speisen';
		$this->statisticsKeyword = 'kulturcafe';
		$this->no_menu_days = [ 0, 6 ];
		$this->lookaheadSafe = true;
		$this->price_nested_info = 'Menü 1 / Menü 2 / Menü 2 BIO';

		parent::__construct();
	}

/*
 * structure:
 * Freitag, 10.04.2015
 *
 * Überbackene Gemüse-Melanzani mit Paradeisrahm und Petersilerdäpfeln
 * Bärlauchrisotto G mit geschmortem Octopus
 *
 * Änderungen vorbehalten.
 */

	protected function get_today_variants() {
		return [
			getGermanDayName() . date(', d.m.Y', $this->timestamp),
		];
	}

	protected function parseDataSource() {
		$dataTmp = html_get_clean($this->dataSource, 'ISO-8859-1');
		if (!$dataTmp) {
			return;
		}
		//return error_log($dataTmp);

		// get menu data for the chosen da
		$data = $this->parse_foods_inbetween_days($dataTmp, getGermanDayName(1), 'Änderungen');
		if (!$data || is_numeric($data)) {
			return $this->data = $data;
		}
		//return error_log($data);

		// set price
		$this->price = [ $this->parse_prices_regex($dataTmp, [ '/\d{1},\d{2}/' ]) ];
		//return error_log(print_r($this->price, true));

		return ($this->data = $data);
	}

}
