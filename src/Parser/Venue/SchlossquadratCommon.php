<?php

namespace App\Parser\Venue;

class SchlossquadratCommon extends FoodGetterVenue {

	function __construct() {
		parent::__construct();
	}
	
	protected function get_today_variants() {
		return [
			getGermanDayName() . ' ' . date('j.n.', $this->timestamp),
			getGermanDayName() . ' ' . date('j.n',  $this->timestamp),
		];
	}
	
	protected function parseDataSource() {
		$dataTmp = \App\Legacy\ContainerHelper::getInstance()->get(\App\Parser\ConverterService::class)->pdfToText($this->dataSource);
		if (!$dataTmp) {
			return;
		}

		$dataTmp = \App\Legacy\ContainerHelper::getInstance()->get(\App\Parser\HtmlService::class)->cleanHtml($dataTmp);

		// get menu data for the chosen day
		$dataTmp = $this->parse_foods_inbetween_days($dataTmp, getGermanDayName(1),
				[], ' ', false);
		if (!$dataTmp || is_numeric($dataTmp)) {
			return ($this->data = $dataTmp);
		}

		// get and set price
		$dataPriceTmp = \App\Legacy\ContainerHelper::getInstance()->get(\App\Parser\HtmlService::class)->getCleanHtml($this->url);
		$price = $this->parse_prices_regex($dataPriceTmp, [ '/Mittags(teller|menü|special) um \d{1},\d{2}/' ]);
		$this->price = is_array($price) ? reset($price) : $price;

		return ($this->data = $dataTmp);
	}

}
