<?php

class AltesFassl extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Zum Alten Fassl';
		$this->address = 'Ziegelofengasse 37, 1050 Wien';
		$this->addressLat = '48.191498';
		$this->addressLng = '16.3604868';
		$this->url = 'http://www.zum-alten-fassl.at/';
		$this->dataSource = 'http://www.zum-alten-fassl.at/mittagsmenues.html';
		$this->menu = 'http://www.zum-alten-fassl.at/standard-karte.html';
		$this->statisticsKeyword = 'zum-alten-fassl';
		$this->no_menu_days = array(0, 6);
		$this->lookaheadSafe = true;

		parent::__construct();
	}

	protected function get_today_variants() {
		$today_variants[] = date('d.m.', $this->timestamp);
		$today_variants[] = date('d.m', $this->timestamp);
		return $today_variants;
	}

	protected function parseDataSource() {
		$dataTmp = file_get_contents($this->dataSource);
		if ($dataTmp === FALSE)
			return;

		if (stripos($dataTmp, 'urlaub') !== false)
			return ($this->data = VenueStateSpecial::Urlaub);

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
		$tomorrow = getGermanDayName(1);
		$posEnd = mb_stripos($dataTmp, $tomorrow, $posStart);
		// last day of the week
		if (!$posEnd) {
			$posEnd = mb_stripos($dataTmp, 'Montag', $posStart);
			if (!$posEnd) {
				$posEnd = mb_stripos($dataTmp, 'MENÜ 1', $posStart);
				if (!$posEnd)
					return;
			}
		}
		$data = mb_substr($dataTmp, $posStart, $posEnd-$posStart);
		$data = strip_tags($data, '<br>');
		$data = str_ireplace(array("<br />","<br>","<br/>"), "\n", $data);
		// remove unwanted stuff
		$data = html_entity_decode($data);
		$data = htmlentities($data);
		$data = str_replace(array('&nbsp;'), '', $data);
		$data = html_entity_decode($data);
		$data = str_replace(array('1)', '2)', '3)'), '', $data);
		// remove multiple newlines
		$data = preg_replace("/([ ]*\n)+/i", "\n", $data);
		$data = trim($data);
		//return error_log($data);
		$foods = explode("\n", $data);
		// removes empty stuff in array
		for ($i = 0; $i<count($foods); $i++) {
			$foods[$i] = str_replace(array("\n", "\r"), '', $foods[$i]);
		}
		$foods = array_filter($foods, 'strlen');
		// set food
		$menu_marker = '***';
		$menu_marker_found = false;
		$menu = array();
		$soup = null;
		//return error_log(print_r($foods, true));
		$cnt = 0;
		foreach ($foods as $food) {
			if (strpos($food, $menu_marker) !== false)
				$menu_marker_found = true;
			else if (!$menu_marker_found)
				$soup .= cleanText($food) . ' ';
			else
				$menu[$cnt++] = cleanText($food);
		}
		$this->data = cleanText($soup);
		$cnt = 1;
		foreach ($menu as $item) {
			$this->data .= "<br />$cnt. $item";
			$cnt++;
		}
		//return error_log($this->data);

		// set date
		$this->date = $today;

		// set price
		$this->price = array('6,10', '7,30', '5,70');
		// adept if less than 3 menus found
		$this->price = array_slice($this->price, 0, count($foods) - 1);

		return $this->data;
	}
}
