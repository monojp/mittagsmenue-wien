<?php

class MINIRESTAURANT extends FoodGetterVenue {

	function __construct() {
		$this->title = 'MINIRESTAURANT';
		//$this->title_notifier = 'NEU';
		$this->address = 'Marchettigasse 11, 1060 Wien';
		$this->addressLat = 48.192327;
		$this->addressLng = 16.349121;
		$this->url = 'http://www.minirestaurant.at/';
		$this->dataSource = 'http://www.minirestaurant.at/system/controllers/ajax_bridge.php';
		$this->menu = $this->url;
		$this->statisticsKeyword = 'minirestaurant';
		$this->no_menu_days = [ 0, 6 ];
		$this->lookaheadSafe = true;
		$this->price_nested_info = 'ohne Dessert / mit Dessert';

		parent::__construct();
	}

/*
 * structure:
 * Freitag
 * Mediterran Fischsuppe(D)
 * Gefüllte Hühnerkeule mit Risi-Pis und Essiggurken (A,G,C)
 *
 *
 * Mo-Fr 12-14:30 Uhr
 */

	protected function get_today_variants() {
		return [
			getGermanDayName(),
		];
	}

	protected function parseDataSource() {
		$dataTmp = curl_post_helper($this->dataSource,
				[ 'action' => 'loadContent', 'page' => 'DAILY']);
		if (
			($dataTmp = json_decode($dataTmp, true)) === null ||
			empty($dataTmp['content']) ||
			!is_string($dataTmp['content'])
		) {
			return;
		}
		$dataTmp = html_clean($dataTmp['content']);
		$dataTmp = cleanText($dataTmp);
		if (!$dataTmp) {
			return;
		}
		//return error_log($dataTmp);

		// get menu data for the chosen da
		$data = $this->parse_foods_inbetween_days($dataTmp, getGermanDayName(1), 'Mo-Fr');
		if (!$data || is_numeric($data)) {
			return $this->data = $data;
		}
		//return error_log($data);

		// set price
		$this->price = [ $this->parse_prices_regex($dataTmp, [ '/\d{1},\d{2}/' ]) ];
		//return error_log(print_r($this->price, true));

		return ($this->data = $data);
	}

}
