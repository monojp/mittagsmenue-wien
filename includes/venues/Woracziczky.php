<?php

class Woracziczky extends FoodGetterVenue {

	const FACEBOOK_ID = 350394680695;

	function __construct() {
		$this->title = 'Woracziczky';
		//$this->title_notifier = 'UPDATE';
		$this->address = 'Spengergasse 52, 1050 Wien';
		$this->addressLat = 48.189343;
		$this->addressLng = 16.352982;
		$this->url = 'http://www.woracziczky.at/';
		$this->dataSource = 'https://www.facebook.com/WORACZICZKY';
		$this->menu = 'https://www.facebook.com/pg/WORACZICZKY/photos/';
		$this->no_menu_days = [ 0, 6 ];
		$this->lookaheadSafe = true;

		parent::__construct();
	}

	protected function get_today_variants() {
		return [
			getGermanDayName()
		];
	}

	protected function parseDataSource() {
		$dataTmp = $this->parse_facebook(self::FACEBOOK_ID);
		if (!$dataTmp) {
			return;
		}

		// set price
		$this->price = [ 8.50 ];

		return ($this->data = $dataTmp);
	}

}
