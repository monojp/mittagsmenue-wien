<?php

class SchlossquadratSilberwirt extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Silberwirt';
		$this->address = 'Schloßgasse 21, 1050 Wien';
		$this->addressLat = 48.191094;
		$this->addressLng = 16.3593266;
		$this->url = 'http://www.silberwirt.at/';
		$this->dataSource = 'http://www.silberwirt.at/pdf.php?days=23';
		$this->menu = 'http://www.silberwirt.at/fileadmin/files/silberwirt/pdf/KaSilber151001.pdf';
		$this->statisticsKeyword = 'silberwirt';
		$this->no_menu_days = [ 0 ];
		$this->lookaheadSafe = true;

		parent::__construct();
	}

/*
 * structure
 * Montag 18.5.
 *
 * Knoblauchcremesuppe
 * Fleischknödel mit Kümmelsaft und Sauerkraut
 *
 * Dienstag 19.5.
 *
 * Paradeisercremesuppe
 * Gebackenes Truthahnschnitzel in Nuß¸panade mit Erdäpfelsalat
 */

	protected function get_today_variants() {
		return [
			getGermanDayName() . ' ' . date('j.n.', $this->timestamp),
			getGermanDayName() . ' ' . date('j.n',  $this->timestamp),
			/*date('j.n.', $this->timestamp),
			date('j.n',  $this->timestamp),*/
		];
	}

	protected function parseDataSource() {
		$dataTmp = pdftohtml($this->dataSource);
		if (!$dataTmp) {
			return;
		}
		//return error_log($dataTmp);

		$dataTmp = html_clean($dataTmp);

		// get menu data for the chosen day
		$dataTmp = $this->parse_foods_inbetween_days($dataTmp, getGermanDayName(1),
				[], "\n", false);
		//return error_log($dataTmp);
		if (!$dataTmp || is_numeric($dataTmp)) {
			return ($this->data = $dataTmp);
		}

		// get and set price
		$dataPriceTmp = html_get_clean($this->url);
		$price = $this->parse_prices_regex($dataPriceTmp, [ '/Mittags(teller|menü) um \d{1},\d{2}/' ]);
		//return error_log(print_r($price, true));
		$this->price = is_array($price) ? reset($price) : $price;

		return ($this->data = $dataTmp);
	}

}
