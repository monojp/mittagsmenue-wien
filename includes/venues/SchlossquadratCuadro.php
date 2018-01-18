<?php

class SchlossquadratCuadro extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Cuadro';
		$this->title_notifier = 'NEU';
		$this->address = 'Margaretenstrasse 77, 1050 Wien';
		$this->addressLat = 48.191312;
		$this->addressLng = 16.358338;
		$this->url = 'http://www.cuadro.at/';
		$this->dataSource = 'http://www.cuadro.at/pdf.php?days=23';
		$this->menu = 'http://www.cuadro.at/fileadmin/files/cuadro/pdf/KaCuadro_170501.pdf';
		$this->no_menu_days = [0, 6];
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
		$price = $this->parse_prices_regex($dataPriceTmp, [ '/Mittagsspecial um \d{1},\d{2}/' ]);
		//return error_log(print_r($price, true));
		$this->price = is_array($price) ? reset($price) : $price;

		return ($this->data = $dataTmp);
	}

}
