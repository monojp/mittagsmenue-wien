<?php

namespace App\Parser\Venue;

class Erbsenzaehlerei extends FoodGetterVenue {

	const FACEBOOK_ID = 989556534441500;

	function __construct() {
		$this->title = 'Erbsenzählerei';
		//$this->title_notifier = 'NEU';
		$this->address = 'Pilgramgasse 2, 1050 Wien';
		$this->addressLat = 48.191697;
		$this->addressLng = 16.357873;
		$this->url = 'https://www.xn--die-erbsenzhlerei-0qb.at/';
		$this->dataSource = 'https://www.facebook.com/Die-Erbsenz%C3%A4hlerei-989556534441500';
		$this->menu = 'https://www.xn--die-erbsenzhlerei-0qb.at/zustellung/';
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
		//$this->price = [];

		return ($this->data = $dataTmp);
	}

}
