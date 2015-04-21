<?php

class CafeAmacord extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Amacord';
		//$this->title_notifier = 'NEU';
		$this->address = 'Rechte Wienzeile 15, 1040 Wien';
		$this->addressLat = '48.198478';
		$this->addressLng = '16.364092';
		$this->url = 'http://www.amacord-cafe.at/';
		$this->dataSource = 'http://www.amacord-cafe.at/speisen/wochenmen%C3%BC/';
		$this->menu = 'http://www.amacord-cafe.at/speisen/';
		$this->statisticsKeyword = 'amacord';
		$this->no_menu_days = array(0, 6);
		$this->lookaheadSafe = true;
		$this->price_nested_info = 'ohne Suppe / mit Suppe oder wahlweise Salat';

		parent::__construct();
	}

	protected function get_today_variants() {
		$today_variants[] = getGermanDayNameShort() . ':';
		return $today_variants;
	}

	protected function parseDataSource() {
		$dataTmp = file_get_contents($this->dataSource);
		if (stripos($dataTmp, 'urlaub') !== false)
			return ($this->data = VenueStateSpecial::Urlaub);
		//return error_log($dataTmp);

		// check date range
		preg_match('/([\d]+\.)+\s*(-|—|–|bis)\s*([\d]+\.)+/', $dataTmp, $date_check);
		if (empty($date_check) || !isset($date_check[0]))
			return;
		$date_check = explode_by_array(array('-', '—', '–', 'bis'), $date_check[0]);
		$date_check = array_map('trim', $date_check);
		$date_start = strtotimep($date_check[0], '%d.%m', $this->timestamp);
		$date_end   = strtotimep($date_check[1], '%d.%m', $this->timestamp);
		//error_log(print_r($date_check, true));
		//error_log(date('d.m.Y', $date_start));
		//return error_log(date('d.m.Y', $date_end));
		if ($this->timestamp < $date_start || $this->timestamp > $date_end)
			return;

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
		$posEnd = mb_stripos($dataTmp, getGermanDayNameShort(1) . ':', $posStart);
		// last day of the week
		if ($posEnd === false)
			$posEnd = mb_stripos($dataTmp, 'Guten Appetit', $posStart);
		if ($posEnd === false)
			return;
		//return error_log($posEnd);

		$data = mb_substr($dataTmp, $posStart, $posEnd-$posStart);
		$data = strip_tags($data);
		// remove unwanted stuff
		$data = str_replace(array('&nbsp;'), '', $data);
		$data = str_ireplace(array("<br />","<br>","<br/>"), "\r\n", $data);
		$data = preg_replace("/([a-z])\n([a-z])/i", '$1 $2', $data);
		// remove multiple newlines
		$data = preg_replace("/(\n)+/i", "\n", $data);
		$data = trim($data);
		//return error_log($data);
		// split per new line
		$foods = explode("\n", $data);
		//return error_log(print_r($foods, true));

		$data = null;
		foreach ($foods as $food) {
			$food = cleanText($food);
			// nothing found
			if (empty($food))
				continue;
			// menu part
			else
				$data = empty($data) ? $food : "${data} ${food}";
		}
		//return error_log($data);

		$this->data = $data;

		// set date
		$this->date = $today;

		preg_match_all('/\d{1},\d{2}/', $dataTmp, $prices);
		$prices = array_map(function (&$val) { return str_replace(',', '.', trim($val, ' ,.&€')); }, $prices[0]);
		//return error_log(print_r($prices, true));
		$this->price = array($prices);

		return $this->data;
	}
}
