<?php

namespace App\Parser\Venue;

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
		$this->no_menu_days = [ 0, 6 ];
		$this->lookaheadSafe = true;
		$this->price_nested_info = 'mit Suppe / ohne Suppe';

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
		$dataTmp = \App\Legacy\ContainerHelper::getInstance()->get(\App\Parser\HtmlService::class)->getCleanHtml($this->dataSource);
		if (!$dataTmp ) {
			return;
		}

		// strip full dates (which appear in the page header)
		$dataTmp = preg_replace('/\d{2}\.\d{2}\.\d{4}/', '', $dataTmp);

		// get menu data for the chosen day
		$data = $this->parse_foods_inbetween_days($dataTmp,
				date('d.', strtotime('+1 day', $this->timestamp)),
				[ 'MENÜPREIS', 'MENÜ-ALTERNATIVE', 'NEWSLETTER', 'Bierometer', '©' ]);
		if (!$data || is_numeric($data)) {
			return ($this->data = $data);
		}

		$this->price = [$this->parse_prices_regex($dataTmp, ['/€ [\d],[\d]{2}/'])];

		return ($this->data = $data);
	}

}
