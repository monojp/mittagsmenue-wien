<?php

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
		$dataTmp = pdftohtml($this->dataSource);
		if (!$dataTmp) {
			return;
		}

		$dataTmp = html_clean($dataTmp);

		// get menu data for the chosen day
		$dataTmp = $this->parse_foods_inbetween_days($dataTmp, getGermanDayName(1),
				[], ' ', false);
		if (!$dataTmp || is_numeric($dataTmp)) {
			return ($this->data = $dataTmp);
		}

		// get and set price
		$dataPriceTmp = html_get_clean($this->url);
		$price = $this->parse_prices_regex($dataPriceTmp, [ '/Mittags(teller|menü|special) um \d{1},\d{2}/' ]);
		$this->price = is_array($price) ? reset($price) : $price;

		return ($this->data = $dataTmp);
	}

}
