<?php

class Gondola extends FoodGetterVenue {

	function __construct() {
		$this->title             = 'Gondola';
		$this->title_notifier    = 'NEU';
		$this->address           = 'Schönbrunnerstraße 70, 1050 Wien';
		$this->addressLat        = 48.189628;
		$this->addressLng        = 16.352214;
		$this->url               = 'http://www.gondola.at/';
		$this->dataSource        = 'http://www.gondola.at/files/menu/menu.pdf';
		$this->menu              = 'http://www.gondola.at/files/tages/tageskarte.pdf';
		$this->statisticsKeyword = 'gondola';
		$this->no_menu_days      = [ 0, 6 ];
		$this->lookaheadSafe     = true;

		parent::__construct();
	}

	protected function get_today_variants() {
		$today_variants[] = mb_strtolower(getGermanDayName());
		return $today_variants;
	}

	protected function parseDataSource() {
		$dataTmp = pdftotext($this->dataSource);
		if (!$dataTmp)
			return;
		//return error_log($dataTmp);

		// check date range
		if (!$this->in_date_range_string($dataTmp, $this->timestamp, '%d.%m', '%d.%m'))
			return;

		// check menu food count
		if ($this->get_holiday_count($dataTmp) + $this->get_soup_count($dataTmp) != 5)
			return;

		$this->parse_foods_independant_from_days($dataTmp, "\n");
		//return error_log($dataTmp);

		$this->price = [ '7,90', '6,90', '7,90' ];

		return ($this->data = $dataTmp);
	}

}