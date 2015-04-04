<?php

class Lambrecht extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Lambrecht';
		//$this->title_notifier = 'NEU';
		$this->address = 'Lambrechtgasse 9, 1040 Wien';
		$this->addressLat = '48.190402';
		$this->addressLng = '16.364595';
		$this->url = 'http://www.lambrecht.wien/';
		$this->dataSource = 'http://www.lambrecht.wien/tagesteller/';
		$this->menu = 'http://www.lambrecht.wien/aus-kueche-und-keller/';
		$this->statisticsKeyword = 'lambrecht';
		$this->no_menu_days = array(0, 6);
		$this->lookaheadSafe = true;
		$this->price_nested_info = 'Mittagsteller mit Suppe / Tagessuppe / Mittagsteller';

		parent::__construct();
	}

/*
 * structure:
 * Donnerstag, 12. Februar
 * – Melanzanicremesuppe (A, F); VEGAN
 * – Coq au Vin mit Erdäpfeln (L, O)
 * – Mediterrane Nudelpfanne mit Räuchertofu (A, F); VEGAN
 */

	protected function get_today_variants() {
		$today_variants[] = getGermanDayName() . date(', d. ', $this->timestamp) . getGermanMonthName();
		$today_variants[] = getGermanDayName() . date(', j. ', $this->timestamp) . getGermanMonthName();
		return $today_variants;
	}

	protected function parseDataSource() {
		$dataTmp = file_get_contents($this->dataSource);
		if ($dataTmp === FALSE)
			return;

		if (stripos($dataTmp, 'urlaub') !== false)
			return ($this->data = VenueStateSpecial::Urlaub);

		// fix unclean data by replacing tabs with spaces
		$dataTmp = str_replace(array("\t", "\r"), array(' ', ' '), $dataTmp);
		// remove multiple spaces
		$dataTmp = preg_replace('/( )+/', ' ', $dataTmp);
		//return error_log($dataTmp);

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
		$posEnd = mb_stripos($dataTmp, getGermanDayName(1), $posStart);
		// last day of the week
		if ($posEnd === false)
			$posEnd = mb_stripos($dataTmp, 'Wir wünschen', $posStart);
		if ($posEnd === false)
			return;
		//return error_log($posEnd);

		$data = mb_substr($dataTmp, $posStart, $posEnd-$posStart);
		$data = strip_tags($data, '<br>');
		// remove unwanted stuff
		$data = str_replace(array('&nbsp;'), '', $data);
		$data = str_ireplace(array("<br />","<br>","<br/>"), "\r\n", $data);
		$data = preg_replace("/([a-z])\n([a-z])/i", '$1 $2', $data);
		// strip strange bullet character
		$data = str_replace(array('&#8211;', '–'), array('', ''), $data);
		// remove multiple newlines
		$data = preg_replace("/(\n)+/i", "\n", $data);
		$data = trim($data);
		//return error_log($data);
		// split per new line
		$foods = explode("\n", $data);
		//return error_log(print_r($foods, true));

		$data = null;
		$cnt = 1;
		foreach ($foods as $food) {
			$food = cleanText($food);
			if (!empty($food)) {
				// starter
				if (!$data) {
					$data = $food;
				}
				// new food
				else {
					$data .= "\n${cnt}. $food";
					$cnt++;
				}
			}
		}
		$data = str_replace("\n", "<br />", $data);
		//return error_log(print_r($data, true));
		$this->data = $data;

		// set date
		$this->date = $today;

		// set price
		$prices = array();
		$startPos = striposAfter($dataTmp, 'Mittagsteller mit Suppe:');
		$endPos   = mb_stripos($dataTmp, ';', $startPos);
		$prices[0] = strip_tags(mb_substr($dataTmp, $startPos, $endPos - $startPos));
		$startPos = striposAfter($dataTmp, ' Tagessuppe:', $startPos);
		$endPos   = mb_stripos($dataTmp, ';', $startPos);
		$prices[1] = strip_tags(mb_substr($dataTmp, $startPos, $endPos - $startPos));
		$startPos = striposAfter($dataTmp, ' Mittagsteller:', $startPos);
		$endPos   = mb_stripos($dataTmp, '&nbsp;', $startPos);
		$prices[2] = strip_tags(mb_substr($dataTmp, $startPos, $endPos - $startPos));
		foreach ($prices as &$price) {
			$price = str_replace(array('€', 'EUR'), array('', ''), $price);
			$price = trim($price);
		}
		unset($price);
		//return error_log(print_r($prices, true));
		$this->price = array($prices);

		return $this->data;
	}
}
