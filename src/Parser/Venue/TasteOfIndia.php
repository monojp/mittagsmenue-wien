<?php

namespace App\Parser\Venue;

use App\Legacy\ContainerHelper;
use App\Parser\HttpService;

class TasteOfIndia extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Taste of India';
		$this->address = 'Margaretenstraße 34, 1040 Wien';
		$this->addressLat = 48.1959393;
		$this->addressLng = 16.3641738;
		$this->url = 'http://www.taste-of-india.at/';
		$this->dataSource = 'http://www.taste-of-india.at/mittagsmenue/';
		$this->menu = 'http://www.taste-of-india.at/speisen/';
		$this->no_menu_days = [ 0, 6 ];
		$this->lookaheadSafe = true;

		parent::__construct();
	}

	protected function get_today_variants() {
		return [
			date_offsetted('d.m.y'),
			date_offsetted('d:m:y'),
			date_offsetted('d,m,y'),
			date_offsetted('d.m.Y'),
			date_offsetted('d:m:Y'),
			date_offsetted('d,m,Y'),
			date_offsetted('d.m,Y'),
		];
	}

	protected function parseDataSource() {
		// get pdf link
		$dataTmp = ContainerHelper::getInstance()->get(HttpService::class)->getBodyContents($this->dataSource);
		if (!$dataTmp) {
			return;
		}
		$dataTmp = preg_match('/("|\')(.*?\.pdf)("|\')/i', $dataTmp, $matches);
		//return error_log(print_r($matches, true)) && false;
		if (!isset($matches[2]) || empty($matches[2])) {
			return;
		}
		$this->dataSource = trim($matches[2]);

		// download pdf data
		$dataTmp = ContainerHelper::getInstance()->get(\App\Parser\ConverterService::class)->pdfToText($this->dataSource);
		if (!$dataTmp) {
			return;
		}
		//~ return error_log($dataTmp) && false;

		// get menu data for the chosen day
		$data = $this->parse_foods_inbetween_days($dataTmp, getGermanDayName(1),
				[ 'Newsletter', 'Join us', 'Copyright' ]);
		//return error_log($data) && false;
		if (!$data || is_numeric($data)) {
			return $this->data = $data;
		}

		// set price
		$this->price = $this->parse_prices_regex($dataTmp, [ '/\d{1},\d{2}\€/' ]);
		//return error_log(print_r($this->price, true)) && false;

		return ($this->data = $data);
	}
}
