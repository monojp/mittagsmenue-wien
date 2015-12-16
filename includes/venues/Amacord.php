<?php

class CafeAmacord extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Amacord';
		//$this->title_notifier = 'NEU';
		$this->address = 'Rechte Wienzeile 15, 1040 Wien';
		$this->addressLat = 48.198478;
		$this->addressLng = 16.364092;
		$this->url = 'http://www.amacord-cafe.at/';
		$this->dataSource = 'http://www.amacord-cafe.at/speisen/wochenmen%C3%BC-1/';
		$this->menu = 'http://www.amacord-cafe.at/speisen/';
		$this->statisticsKeyword = 'amacord';
		$this->no_menu_days = [ 0, 6 ];
		$this->lookaheadSafe = true;
		$this->price_nested_info = 'ohne Suppe / mit Suppe oder wahlweise Salat';

		parent::__construct();
	}

	protected function get_today_variants() {
		return [
			getGermanDayNameShort() . ':',
		];
	}

	protected function parseDataSource() {
		$dataTmp = html_get_clean($this->dataSource);
		if (!$dataTmp) {
			return;
		}
		//return error_log($dataTmp);

		// check date range
		if (!$this->in_date_range_string($dataTmp, $this->timestamp, '%d.%m', '%d.%m')) {
			return;
		}

		// get menu data for the chosen day
		$data = $this->parse_foods_inbetween_days($dataTmp, getGermanDayNameShort(1) . ':',
				'Guten', "\n", false);
		//return error_log($data);
		if (!$data || is_numeric($data)) {
			return ($this->data = $data);
		}

		$prices = $this->parse_prices_regex($dataTmp, [ '/\d{1},\d{2}/' ]);
		//return error_log(print_r($prices, true));
		$this->price = [ $prices ];

		return ($this->data = $data);
	}

}
