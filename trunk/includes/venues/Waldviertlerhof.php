<?php

class Waldviertlerhof extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Waldviertlerhof';
		$this->title_notifier = 'BETA';
		$this->address = 'Schönbrunnerstrasse 20, 1050 Wien';
		$this->addressLat = '48.193692';
		$this->addressLng = '16.358687';
		$this->url = 'http://www.waldviertlerhof.at/';
		$this->dataSource = 'http://www.waldviertlerhof.at/assets/w4h-mittagsmenue2.pdf';
		$this->menu = 'http://www.waldviertlerhof.at/assets/w4h_speisen_getränke2.pdf';
		$this->statisticsKeyword = 'waldviertlerhof';
		$this->no_menu_days = array(0, 6);
		$this->lookaheadSafe = true;
		$this->price_nested_info = 'Menü / Tagesteller';

		parent::__construct();
	}

	protected function get_today_variants() {
		$today_variants[] = getGermanDayName();
		return $today_variants;
	}

	protected function parseDataSource() {
		//$dataTmp = pdftotxt_ocr($this->dataSource);
		$dataTmp = pdftotext($this->dataSource);
		if (stripos($dataTmp, 'urlaub') !== false)
			return ($this->data = VenueStateSpecial::Urlaub);
		//return error_log($dataTmp);

		// check date range
		preg_match('/[\d]+\.(.)+(-|—|bis)(.)*[\d]+\.(.)+/', $dataTmp, $date_check);
		if (empty($date_check) || !isset($date_check[0]))
			return;
		$date_check = explode_by_array(array('-', '—', 'bis'), $date_check[0]);
		$date_check = array_map('trim', $date_check);
		$date_start = strtotimep($date_check[0], '%d. %B', $this->timestamp);
		$date_end   = strtotimep($date_check[1], '%d. %B', $this->timestamp);
		//return error_log(print_r($date_check, true));
		if ($this->timestamp < $date_start || $this->timestamp > $date_end)
			return;

		// check menu food count
		if (substr_count($dataTmp, 'suppe') != 5)
			return;

		// remove unwanted stuff
		$data = $dataTmp;
		//$data = preg_replace("/([a-z])\n([a-z])/i", '$1 $2', $data);
		// remove multiple newlines
		$data = preg_replace("/(\n)+/i", "\n", $data);
		$data = trim($data);
		//return error_log($data);
		// split per new line
		$foods = explode("\n", $data);
		//return error_log(print_r($foods, true));

		$data = null;
		$foodCount = 1; // 1 is monday, 5 is friday
		$weekday = date('w', $this->timestamp);
		foreach ($foods as $food) {
			$food = cleanText($food);
			// nothing found
			if (empty($food))
				continue;
			// first part of menu (soup)
			else if (mb_strpos($food, 'suppe') !== false) {
				if ($foodCount == $weekday) {
					$data = $food;
				}
				$foodCount++;
			}
			// second part of menu
			else if ($foodCount == ($weekday+1) && !empty($data)) {
				$data .= ", ${food}";
				break;
			}
		}
		//return error_log($data);

		$this->data = $data;

		preg_match('/((.)*€(.*)){2,2}/', $dataTmp, $prices);
		if (empty($prices))
			preg_match('/Menü(.*)|Tagesteller(.*)/', $dataTmp, $prices);
		preg_match_all('/[\d\.\,]+/', $prices[0], $prices);
		$prices = array_map(function (&$val) { return str_replace(',', '.', trim($val, ' ,.&€')); }, $prices[0]);
		//return error_log(print_r($prices, true));
		$this->price = array($prices);

		// set date
		$this->date = getGermanDayName();

		return $this->data;
	}
}
