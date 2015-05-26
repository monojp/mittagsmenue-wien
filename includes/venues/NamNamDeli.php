<?php

class NamNamDeli extends FoodGetterVenue {

	function __construct() {
		$this->title             = 'Nam Nam Deli';
		//$this->title_notifier  = 'NEU';
		$this->address           = 'Webgasse 3, 1060 Wien';
		$this->addressLat        = 48.192375;
		$this->addressLng        = 16.348016;
		$this->url               = 'http://www.nam-nam.at/restaurant/';
		$this->dataSource        = 'http://www.nam-nam.at/restaurant/wochenkarte/';
		$this->menu              = 'http://www.nam-nam.at/restaurant/menue/speisen/';
		$this->statisticsKeyword = 'nam-nam';
		$this->no_menu_days      = [ 0, 1, 6 ];
		$this->lookaheadSafe     = true;

		parent::__construct();
	}

	protected function get_today_variants() {
		$month = (date('n', $this->timestamp) == 1) ? 'Januar' : getGermanMonthName();
		return [
			getGermanDayName() . ', ' . date('j.', $this->timestamp) . ' ' . $month,
			getGermanDayName() . ', ' . date('d.', $this->timestamp) . ' ' . $month,
		];
	}

	protected function parseDataSource() {
		$dataTmp = html_get_clean($this->dataSource);
		if (!$dataTmp )
			return;
		//return error_log($dataTmp);

		// get menu data for the chosen day
		$data = $this->parse_foods_inbetween_days($dataTmp, getGermanDayName(1), [ 'Daily Menu Specials', 'Menus of the Week' ]);
		if (!$data || is_numeric($data))
			return ($this->data = $data);
		//return error_log($data);

		$this->price = $this->parse_prices_regex($dataTmp, [ '/MITTAGSMENÜ NUR \d{1},\d{2}/' ]);
		//return error_log(print_r($this->price, true));
		//$this->price = [ $prices ];

		return ($this->data = $data);
	}

}
