<?php

class Waldviertlerhof extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Waldviertlerhof';
		//$this->title_notifier = 'BETA';
		$this->address = 'Schönbrunnerstrasse 20, 1050 Wien';
		$this->addressLat = 48.193692;
		$this->addressLng = 16.358687;
		$this->url = 'http://www.waldviertlerhof.at/';
		$this->dataSource = 'http://www.waldviertlerhof.at/Mittagsmenue';
		$this->menu = 'http://www.waldviertlerhof.at/Speisekarte';
		$this->statisticsKeyword = 'waldviertlerhof';
		$this->no_menu_days = [ 0, 6 ];
		$this->lookaheadSafe = true;
		$this->price_nested_info = 'Menü / Tagesteller / Fischmenü Freitag';

		parent::__construct();
	}

	protected function get_today_variants() {
		return [
			getGermanDayName(),
		];
	}

	protected function parseDataSource() {
		//$dataTmp = pdftotxt_ocr($this->dataSource);
		//$dataTmp = pdftotext($this->dataSource);
		$dataTmp = html_get_clean($this->dataSource);
		if (!$dataTmp) {
			return;
		}
		//return error_log($dataTmp);

		// check date range
		if (!$this->in_date_range_string($dataTmp, $this->timestamp)) {
			return;
		}

		// check menu food count
		// 7 weil "Sonn- u. Feiertag geschlossen" im text vorkommt
		// UPDATE 2015-10-27: nicht mehr notwendig, da schon direkt nach wochentag gesucht wird
		/*if ($this->get_holiday_count($dataTmp) + $this->get_starter_count($dataTmp) != 7 ) {
			return;
		}*/

		// get menu data for the chosen day
		//$data = $this->parse_foods_independant_from_days($dataTmp, "\n", $prices, true, false);
		$data = $this->parse_foods_inbetween_days($dataTmp, getGermanDayName(1), 'Preise');
		if (!$data || is_numeric($data)) {
			return ($this->data = $data);
		}
		//return error_log($data);

		// set price
		$this->price = [ $this->parse_prices_regex($dataTmp, [ '/\d{1},\d{2}/' ]) ];
		//return error_log(print_r($this->price, true));

		return ($this->data = $data);
	}

}
