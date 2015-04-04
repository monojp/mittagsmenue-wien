<?php

class Gondola extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Gondola';
		$this->title_notifier = 'NEU';
		$this->address = 'Schönbrunnerstraße 70, 1050 Wien';
		$this->addressLat = '48.189628';
		$this->addressLng = '16.352214';
		$this->url = 'http://www.gondola.at/';
		$this->dataSource = 'http://www.gondola.at/files/menu/menu.pdf';
		$this->menu = 'http://www.gondola.at/files/tages/tageskarte.pdf';
		$this->statisticsKeyword = 'gondola';
		$this->no_menu_days = array(0, 6);
		$this->lookaheadSafe = true;

		parent::__construct();
	}

	protected function get_today_variants() {
		$today_variants[] = mb_strtolower(getGermanDayName());
		return $today_variants;
	}

	protected function parseDataSource() {
		$dataTmp = pdftotext($this->dataSource);
		if (stripos($dataTmp, 'urlaub') !== false)
			return ($this->data = VenueStateSpecial::Urlaub);
		//return error_log($dataTmp);

		// check date range
		if (!$this->in_date_range_string($dataTmp, $this->timestamp, '%d.%m', '%d.%m'))
			return;

		// check menu food count
		if ((substr_count($dataTmp, 'feiertag') + substr_count($dataTmp, 'suppe')) != 5)
			return;

		// remove unwanted stuff
		$data = $dataTmp;
		//$data = preg_replace("/([a-z])\n([a-z])/i", '$1 $2', $data);
		// remove multiple newlines
		$data = preg_replace("/(\n)+/i", "\n", $data);
		$data = trim($data);
		//return error_log($data);
		// split per new line
		$foods = explode("\n", $data);
		//return error_log(print_r($foods, true));

		$data = $this->parse_foods_independant_from_days($foods, "\n");
		//return error_log($data);

		$this->data = $data;

		// set date
		$this->date = reset($this->get_today_variants());

		$this->price = array('7,90', '6,90', '7,90');

		return $this->data;
	}
}
