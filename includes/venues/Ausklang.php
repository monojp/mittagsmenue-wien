<?php

class Ausklang extends FoodGetterVenue {

	function __construct() {
		$this->title = 'aus:klang';
		$this->address = 'Kleistgasse 1, 1030 Wien';
		$this->addressLat = 48.1931822;
		$this->addressLng = 16.3906608;
		$this->url = 'http://ausklang.at/';
		$this->dataSource = 'http://ausklang.at/essen/menue.html';
		$this->menu = 'http://ausklang.at/essen.html';
		$this->statisticsKeyword = 'ausklang';
		$this->no_menu_days = [ 0, 6 ];
		$this->lookaheadSafe = true;
		$this->price_nested_info = 'Vorspeise oder Dessert / Vorspeise und Dessert';

		parent::__construct();
	}

	protected function get_today_variants() {
		return [
			mb_strtolower(getGermanDayName()) . ', ' . date('d.', $this->timestamp) . ' '
					. mb_strtolower(getGermanMonthName()),
		];
	}

	protected function parseDataSource() {
		$dataTmp = html_get_clean($this->dataSource);
		if (!$dataTmp) {
			return;
		}

		// get menu data for the chosen day
		$data = $this->parse_foods_inbetween_days($dataTmp, getGermanDayName(1),
				[ 'menü', 'men&uuml;' ]);
		if (!$data || is_numeric($data)) {
			return $this->data = $data;
		}
		//return error_log($data);

		// set price
		$this->price = $this->parse_prices_regex($dataTmp, [ '/\d{1},\d{2}/' ]);
		$this->price = [ array_slice($this->price, 0, 2) ];
		//return error_log(print_r($this->price, true));

		return ($this->data = $data);
	}

}
