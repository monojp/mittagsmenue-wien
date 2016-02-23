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
		$this->dataSource = 'https://facebook.com/WORACZICZKY';
		$this->menu = 'https://facebook.com/WORACZICZKY/mediaset?album=pb.350394680695.-2207520000.1417430572.';
		$this->statisticsKeyword = 'woracziczky';
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
