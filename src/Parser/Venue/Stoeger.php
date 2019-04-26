<?php

namespace App\Parser\Venue;

class Stoeger extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Restaurant Stöger';
		//$this->title_notifier = 'NEU';
		$this->address = 'Ramperstorffergasse 63, 1050 Wien';
		$this->addressLat = 48.190359;
		$this->addressLng = 16.354808;
		$this->url = 'http://www.zumstoeger.at/';
		$this->dataSource = 'http://www.zumstoeger.at/heute.php';
		$this->menu = 'http://www.zumstoeger.at/speisekarte.pdf';
		$this->no_menu_days = [ 0, 6 ];
		$this->lookaheadSafe = true;
		//$this->price_nested_info = 'Mittagsteller mit Suppe / Tagessuppe / Mittagsteller';

		parent::__construct();
	}

/*
 * structure:
 * Mittagsmenü
 * ﻿Dienstag, 18. August 2015
 *
 * Tafelspitzbouillon mit Leberreis
 *
 * Surschnitzel mit gemischtem Salat
 *
 * € 7,80
 */

	protected function get_today_variants() {
		return [
			getGermanDayName() . date(', d. ', $this->timestamp)
					. getGermanMonthName() . date(' Y', $this->timestamp),
			getGermanDayName() . date(',d. ', $this->timestamp)
					. getGermanMonthName() . date(' Y', $this->timestamp),
			getGermanDayName() . date(', d.', $this->timestamp)
					. getGermanMonthName() . date(' Y', $this->timestamp),
			getGermanDayName() . date(',d.', $this->timestamp)
					. getGermanMonthName() . date(' Y', $this->timestamp),
			getGermanDayName() . date(' d.', $this->timestamp)
					. getGermanMonthName() . date(' Y', $this->timestamp),
			getGermanDayName() . date(' d. ', $this->timestamp)
					. getGermanMonthName() . date(' Y', $this->timestamp),
			getGermanDayName() . date(', j. ', $this->timestamp)
					. getGermanMonthName() . date(' Y', $this->timestamp),
			getGermanDayName() . date(',j. ', $this->timestamp)
					. getGermanMonthName() . date(' Y', $this->timestamp),
			getGermanDayName() . date(', j.', $this->timestamp)
					. getGermanMonthName() . date(' Y', $this->timestamp),
			getGermanDayName() . date(',j.', $this->timestamp)
					. getGermanMonthName() . date(' Y', $this->timestamp),
			getGermanDayName() . date(' j.', $this->timestamp)
					. getGermanMonthName() . date(' Y', $this->timestamp),
			getGermanDayName() . date(' j. ', $this->timestamp)
					. getGermanMonthName() . date(' Y', $this->timestamp),
			getGermanDayName() . date(' d.m.Y', $this->timestamp),
			getGermanDayName() . date(' y.n.Y', $this->timestamp),
		];
	}

	protected function parseDataSource() {
		$dataTmp = \App\Legacy\ContainerHelper::getInstance()->get(\App\Parser\HtmlService::class)->getCleanHtml($this->dataSource);
		if (!$dataTmp) {
			return;
		}
		//return error_log($dataTmp);

		// get menu data for the chosen day
		$data = $this->parse_foods_inbetween_days($dataTmp, 'Spezialitäten', [], "\n", false);
		if (!$data || is_numeric($data)) {
			return $this->data = $data;
		}
		//return error_log($data);

		// set price
		$this->price = $this->parse_prices_regex($dataTmp, [ '/\d{1},\d{2}/' ]);
		//return error_log(print_r($this->price, true));

		return ($this->data = $data);
	}

}
