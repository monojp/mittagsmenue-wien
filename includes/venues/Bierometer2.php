<?php

class Bierometer2 extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Bierometer 2';
		//$this->title_notifier = 'NEU';
		$this->address = 'Margaretenplatz 9, 1050 Wien';
		$this->addressLat = 48.192031;
		$this->addressLng = 16.358819;
		$this->url = 'http://www.bierometer-2.at';
		$this->dataSource = 'http://www.bierometer-2.at/menueplan';
		$this->menu = 'http://www.bierometer-2.at/speisekarte/suppen-salate';
		$this->statisticsKeyword = 'bierometer-2';
		$this->no_menu_days = [ 0, 6 ];
		$this->lookaheadSafe = true;

		parent::__construct();
	}

	protected function get_today_variants() {
		return [
			date('j.m.', $this->timestamp),
			date('j.m', $this->timestamp),
			date('d.m.', $this->timestamp),
			date('d.m', $this->timestamp),
		];
	}

	protected function parseDataSource() {
		$dataTmp = html_get_clean($this->dataSource);
		if (!$dataTmp ) {
			return;
		}
		//return error_log($dataTmp);

		// get menu data for the chosen day
		$data = $this->parse_foods_inbetween_days($dataTmp,
				date('d', strtotime('+1 day', $this->timestamp)),
				[ '©', 'Bierometer', 'NEWSLETTER' ]);
		if (!$data || is_numeric($data)) {
			return ($this->data = $data);
		}
		//return error_log($data);

		//$this->price = [ ];
		//return error_log(print_r($this->price, true));

		return ($this->data = $data);
	}

}
