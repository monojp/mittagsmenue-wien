<?php

class SchlossquadratMargareta extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Margareta';
		$this->address = 'Margaretenplatz 2, 1050 Wien';
		$this->addressLat = 48.1916491;
		$this->addressLng = 16.3588115;
		$this->url = 'http://www.margareta.at/';
		$this->dataSource = 'http://www.margareta.at/pdf.php?days=23';
		$this->menu = 'http://www.margareta.at/fileadmin/files/margareta/pdf/KAMargareta151001.pdf';
		$this->statisticsKeyword = 'margareta';
		$this->no_menu_days = [ 0 ];
		$this->lookaheadSafe = true;

		parent::__construct();
	}

/*
 * structure
 * Freitag 22.5.
 *
 * Pizza mit Paradeisern, Mozzarella, Champignons und Artischocken
 *
 * Samstag 23.5.
 *
 * Penne Siziliana mit Paradeiser, Melanzani, frischem Ricotta, Basilikum
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
				[], ' ', false);
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
