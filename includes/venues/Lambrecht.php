<?php

class Lambrecht extends FoodGetterVenue {

	function __construct() {
		$this->title             = 'Lambrecht';
		//$this->title_notifier  = 'NEU';
		$this->address           = 'Lambrechtgasse 9, 1040 Wien';
		$this->addressLat        = 48.190402;
		$this->addressLng        = 16.364595;
		$this->url               = 'http://www.lambrecht.wien/';
		$this->dataSource        = 'http://www.lambrecht.wien/mittagsteller/';
		$this->menu              = 'http://www.lambrecht.wien/aus-kueche-und-keller/';
		$this->statisticsKeyword = 'lambrecht';
		$this->no_menu_days      = [ 0, 6 ];
		$this->lookaheadSafe     = true;
		$this->price_nested_info = 'Mittagsteller mit Suppe / Tagessuppe / Mittagsteller';

		parent::__construct();
	}

/*
 * structure:
 * Donnerstag, 12. Februar
 * – Melanzanicremesuppe (A, F); VEGAN
 * – Coq au Vin mit Erdäpfeln (L, O)
 * – Mediterrane Nudelpfanne mit Räuchertofu (A, F); VEGAN
 */

	protected function get_today_variants() {
		return [
			getGermanDayName() . date(', d. ', $this->timestamp) . getGermanMonthName(),
			getGermanDayName() . date(', j. ', $this->timestamp) . getGermanMonthName(),
		];
	}

	protected function parseDataSource() {
		$dataTmp = html_get_clean($this->dataSource);
		if (!$dataTmp)
			return;
		//return error_log($dataTmp);

		// get menu data for the chosen day
		$data = $this->parse_foods_inbetween_days($dataTmp, getGermanDayName(1), 'Wir wünschen');
		if (!$data || is_numeric($data))
			return $this->data = $data;
		//return error_log($data);

		// set price
		$this->price = [ array_slice($this->parse_prices_regex($dataTmp, [ '/\d{1},\d{2}/' ]), 0, 3) ];
		//return error_log(print_r($this->price, true));

		return ($this->data = $data);
	}

}
