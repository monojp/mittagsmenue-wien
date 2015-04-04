<?php

class RadioCafe extends FoodGetterVenue {

	function __construct() {
		$this->title = 'RadioCafe';
		$this->title_notifier = 'NEU';
		$this->address = 'Argentinierstraße 30a, 1040 Wien';
		$this->addressLat = '48.194522';
		$this->addressLng = '16.373029';
		$this->url = 'http://www.kulturcafe.eu/index.php?home';
		$this->dataSource = 'http://www.kulturcafe.eu/index.php?wochenkarte';
		$this->menu = 'http://www.kulturcafe.eu/index.php?speisen';
		$this->statisticsKeyword = 'kulturcafe';
		$this->no_menu_days = array(0, 6);
		$this->lookaheadSafe = true;

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
		$today_variants[] = getGermanDayName() . date(', d.m.Y', $this->timestamp);
		return $today_variants;
	}

	protected function parseDataSource() {
		$dataTmp = html_get_clean($this->dataSource);
		if (!$dataTmp)
			return;

		if (stripos($dataTmp, 'urlaub') !== false)
			return ($this->data = VenueStateSpecial::Urlaub);

		// get menu data for the chosen da
		$data = $this->parse_foods_inbetween_days($dataTmp, getGermanDayName(1), 'Änderungen');
		if (!$data)
			return;
		//return error_log($data);

		// split per new line
		$foods = explode("\n", $data);
		//return error_log(print_r($foods, true));

		$data = '';
		$cnt = 1;
		foreach ($foods as $food) {
			$food = cleanText($food);
			if (!empty($food)) {
				$data .= empty($data) ? "${cnt}. $food" : "<br>${cnt}. $food";
				$cnt++;
			}
		}
		//return error_log(print_r($data, true));

		// set price
		$prices = $this->parse_prices_regex($dataTmp, array(
			'/(Preis Menü 1).*/',
			'/(Preis Menü 2).*/',
		));
		//return error_log(print_r($prices, true));
		$this->price = $prices;

		return ($this->data = $data);
	}
}
