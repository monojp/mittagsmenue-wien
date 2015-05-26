<?php

require_once(__DIR__ . '/../MensaHelper.php');

class MensaSchroedinger extends FoodGetterVenue {

	function __construct() {
		$this->title             = 'Mensa Café Schrödinger';
		//$this->title_notifier  = 'NEU';
		$this->address           = 'Wiedner Hauptstraße 8-10, 1040 Wien';
		$this->addressLat        = 48.198710;
		$this->addressLng        = 16.367576;
		$this->url               = 'http://menu.mensen.at/index/index/locid/52';
		$this->dataSource        = 'http://menu.mensen.at/index/index/locid/52';
		$this->menu              = $this->dataSource;
		$this->statisticsKeyword = 'mensen';
		$this->no_menu_days      = [ 0, 6 ];
		$this->lookaheadSafe     = true;
		$this->price_nested_info = 'mit Dessert / ohne Dessert';

		parent::__construct();
	}

	protected function get_today_variants() {
		$today_variants[] = date('d.m.', $this->timestamp);
		return $today_variants;
	}

	protected function parseDataSource() {
		$dataTmp = file_get_contents($this->dataSource);
		if (!$dataTmp)
			return;

		$dataTmp = html_entity_decode($dataTmp);
		$dataTmp = htmlentities($dataTmp);
		$dataTmp = str_replace([ '&nbsp;' ], ' ', $dataTmp);
		$dataTmp = html_entity_decode($dataTmp);

		// get menu data for the chosen day
		$today_variants = $this->get_today_variants();
		//return error_log(print_r($today, true));

		$today = null;
		foreach ($today_variants as $today) {
			$posStart = strposAfter($dataTmp, $today);
			if ($posStart !== false)
				break;
		}
		if ($posStart === false)
			return;
		//return error_log($posStart);

		// get menus via helper function
		$price_return = null;
		$data[] = '1. ' . mensa_menu_get($dataTmp, '<h2>Menü Classic 1</h2>', $this->timestamp, $price_return);
		$data[] = '2. ' . mensa_menu_get($dataTmp, '<h2>Menü Classic 2</h2>', $this->timestamp, $price_return);
		$data = implode('<br />', $data);
		//var_export($price);
		//return;
		$this->data = $data;

		// set date
		$this->date = $today;

		// get prices
		$price = [];
		// menu classic 1
		$posStart = strposAfter($dataTmp, '<h2>Menü Classic 1</h2>');
		$posStart = strposAfter($dataTmp, '€', $posStart);
		$posEnd = strpos($dataTmp, '<div', $posStart);
		$data = mb_substr($dataTmp, $posStart, $posEnd - $posStart);
		$data = strip_tags($data);
		$data = str_replace('€', '', $data);
		$data = explode('/', $data);
		foreach ($data as &$data_element) {
			$data_element = cleanText($data_element);
		}
		unset($data_element);
		$data = array_filter($data, function($var) { return !empty($var); });
		$price[] = $data;
		// menu classic 2, not needed, because the price is the same
		/*$posStart = strposAfter($dataTmp, '<h2>Menü Classic 2</h2>');
		$posStart = strposAfter($dataTmp, '€', $posStart);
		$posEnd = strpos($dataTmp, '<div', $posStart);
		$data = mb_substr($dataTmp, $posStart, $posEnd - $posStart);
		$data = strip_tags($data);
		$data = str_replace('€', '', $data);
		$data = explode('/', $data);
		foreach ($data as &$data_element) {
			$data_element = cleanText($data_element);
		}
		unset($data_element);
		$data = array_filter($data, function($var) { return !empty($var); });
		$price[] = $data;*/

		//var_export($price);
		//return;
		$this->price = $price;

		//var_export($data);
		//return;
		return $this->data;
	}

}
