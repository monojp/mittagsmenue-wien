<?php

class Waldviertlerhof extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Waldviertlerhof';
		//$this->title_notifier = 'BETA';
		$this->address = 'Schönbrunnerstrasse 20, 1050 Wien';
		$this->addressLat = 48.193692;
		$this->addressLng = 16.358687;
		$this->url = 'http://www.waldviertlerhof.at/';
		$this->dataSource = 'http://www.waldviertlerhof.at/Mittagsmenue';
		$this->menu = 'http://www.waldviertlerhof.at/speisenkarte';
		$this->statisticsKeyword = 'waldviertlerhof';
		$this->no_menu_days = [ 0, 6 ];
		$this->lookaheadSafe = true;
		$this->price_nested_info = 'Menü / Tagesteller / Fischmenü Freitag';

		parent::__construct();
	}

	protected function get_today_variants() {
		return [
			getGermanDayName(),
		];
	}

	protected function parseDataSource() {
		$dataTmp = html_get_clean($this->dataSource);
		if (!$dataTmp) {
			return;
		}
		//return error_log($dataTmp);

		// try to set parsing offset to avoid non useful data
		$dataTmp = mb_substr($dataTmp, mb_strripos($dataTmp, 'Mittagsmenü'));

		// check date range
		if (!$this->in_date_range_string($dataTmp, $this->timestamp)) {
			return;
		}

		// get menu data for the chosen day
		$data = $this->parse_foods_inbetween_days($dataTmp, getGermanDayName(1),
				[ 'NEU', 'Menü', 'Preise' ], "\n", false);
		//return error_log($data);
		if (!$data || is_numeric($data)) {
			return ($this->data = $data);
		}

		// set price
		$this->price = [ $this->parse_prices_regex($dataTmp, [ '/\d{1},\d{2}/' ]) ];
		//return error_log(print_r($this->price, true));

		return ($this->data = $data);
	}

}
