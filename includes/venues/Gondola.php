<?php

class Gondola extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Gondola';
		$this->title_notifier = 'REVIVAL';
		$this->address = 'Schönbrunnerstraße 70, 1050 Wien';
		$this->addressLat = 48.189628;
		$this->addressLng = 16.352214;
		$this->url = 'http://www.gondola.at/';
		$this->dataSource = 'http://www.gondola.at/ristorante/';
		$this->menu = 'http://www.gondola.at/ristorante/#1495145120688-21d3555d-3784';
		$this->no_menu_days = [ 0, 6 ];
		$this->lookaheadSafe = true;

		parent::__construct();
	}

	protected function get_today_variants() {
		return [
			mb_strtolower(getGermanDayName())
		];
	}

	protected function parseDataSource() {
		$dataTmp = file_get_contents($this->dataSource);
		if (!$dataTmp) {
			return;
		}

		// get menu data only via html regex
		preg_match('/(<table.*?< ?\/table)/s', $dataTmp, $matches);
		if (count($matches) < 2) {
			return;
		}
		$dataTmp = html_clean($matches[1]);
		//return error_log($dataTmp) && false;

		// check date range
		if (!$this->in_date_range_string($dataTmp, $this->timestamp, '%d.%m', '%d.%m')) {
			return;
		}

		// parse first 3 prices (old method, which should be cleaned up)
		$this->price = array_slice($this->parse_prices_regex($dataTmp, ['/[0-9],[0-9]{2,2}/']),
			0,3);
		//return error_log(print_r($this->price, true)) && false;

		$dataTmp = $this->parse_foods_inbetween_days($dataTmp, getGermanDayName(1));
		//return error_log($dataTmp) && false;

		return ($this->data = $dataTmp);
	}

}
