<?php

class TasteOfIndia extends FoodGetterVenue {

	function __construct() {
		$this->title             = 'Taste of India';
		$this->address           = 'Margaretenstraße 34, 1040 Wien';
		$this->addressLat        = 48.1959393;
		$this->addressLng        = 16.3641738;
		$this->url               = 'http://www.taste-of-india.at/';
		$this->dataSource        = 'http://www.taste-of-india.at/mittagsmenue.html';
		$this->menu              = 'http://www.taste-of-india.at/speisekarte/speisemain2.html';
		$this->statisticsKeyword = 'taste-of-india';
		$this->no_menu_days      = [ 0, 6 ];
		$this->lookaheadSafe     = true;

		parent::__construct();
	}

	protected function get_today_variants() {
		$today_variants[] = date_offsetted('d.m.y');
		return $today_variants;
	}

	protected function parseDataSource() {
		$dataTmp = file_get_contents($this->dataSource);
		if (!$dataTmp)
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
		$posEnd = mb_stripos($dataTmp, getGermanDayName(1), $posStart);
		// last day of the week
		if (!$posEnd)
			$posEnd = mb_stripos($dataTmp, '</table>', $posStart);
		$data = mb_substr($dataTmp, $posStart, $posEnd-$posStart);

		$data = strip_tags($data);
		$data = str_replace([ '1', '2', '3', '4', "\t" ], '', $data);
		$data = preg_replace("/([a-z])\n([a-z])/i", '$1 $2', $data);
		// remove multiple newlines
		$data = preg_replace("/(\n)+/i", "\n", $data);
		$data = trim($data);

		$foods = explode("\n", $data);
		//unset($foods[0]);	// date
		$data = cleanText($foods[0]);	// soup
		unset($foods[0]);
		for ($i=1; $i<=count($foods); $i++) {
			if ($i % 2 == 1) {
				$nr = round($i / 2);
				$data .= "\n$nr. ".cleanText($foods[$i]);
			}
			else {
				$data .= ': ' . cleanText($foods[$i]);
			}
		}

		$data = str_replace("\n", "<br />", $data);
		$this->data = $data;

		// set date
		$this->date = $today;

		// set price
		$posStart = striposAfter($dataTmp, 'Men&uuml;');
		$posEnd = mb_stripos($dataTmp, '&euro;', $posStart);
		$this->price = cleanText(mb_substr($dataTmp, $posStart, $posEnd-$posStart));

		return $this->data;
	}
}
