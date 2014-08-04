<?php

class MensaFreihaus extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Mensa Freihaus';
		//$this->title_notifier = 'NEU';
		$this->address = 'Wiedner Hauptstraße 8-10, 1040 Wien';
		$this->addressLat = '48.198710';
		$this->addressLng = '16.367576';
		$this->url = 'http://menu.mensen.at/index/index/locid/9';
		$this->dataSource = 'http://menu.mensen.at/index/index/locid/9';
		$this->statisticsKeyword = 'mensen';
		$this->no_menu_days = array(0, 6);
		$this->lookaheadSafe = true;
		$this->price_nested_info = 'kleine / große Portion';

		parent::__construct();
	}

	private function mensa_menu_get($dataTmp, $title_search, &$price_return=null) {
		$posStart = strnposAfter($dataTmp, 'menu-item-text">', strrpos($dataTmp, $title_search), date('N', $this->timestamp));
		if ($posStart === false)
			return null;
		$posEnd = mb_stripos($dataTmp, '</div>', $posStart);
		$data = mb_substr($dataTmp, $posStart, $posEnd - $posStart);
		$data = strip_tags($data, '<br>');
		//var_export($data);
		//return;
		// remove multiple newlines
		$data = preg_replace("/(\n)+/i", "\n", $data);
		$data = trim($data);
		// split per new line
		$foods = explode("\n", $data);
		//var_export($foods);
		//return;

		$data = null;
		$cnt = 1;
		foreach ($foods as $food) {
			$food = cleanText($food);
			if (!empty($food)) {
				if ($cnt == 1)
					$data .= $food;
				else if ($cnt == 2 && mb_stripos($data, 'suppe') !== false)
					$data .= ", $food";
				else if (strpos($food, '€') !== false)
					$price_return = $food;
				else
					$data .= " $food";
				$cnt++;
			}
		}
		return empty($data) ? '-' : $data;
	}

	protected function parseDataSource() {
		$dataTmp = file_get_contents($this->dataSource);
		if ($dataTmp === FALSE)
			return;

		if (stripos($dataTmp, 'urlaub') !== false)
			return ($this->data = VenueStateSpecial::Urlaub);

		$dataTmp = html_entity_decode($dataTmp);
		$dataTmp = htmlentities($dataTmp);
		$dataTmp = str_replace(array('&nbsp;'), ' ', $dataTmp);
		$dataTmp = html_entity_decode($dataTmp);
		//$dataTmp = strip_tags($dataTmp, '<br>');
		//var_export($dataTmp);
		//return;

		// check if correct week
		$today = date('d.m.', $this->timestamp);
		if (strpos($dataTmp, $today) === false)
			return;

		// get menus via helper function
		$price_return = null;
		$data[] = '1. ' . $this->mensa_menu_get($dataTmp, '<h2>Menü Classic 1</h2>', $price_return);
		$data[] = '2. ' . $this->mensa_menu_get($dataTmp, '<h2>Menü Classic 2</h2>', $price_return);
		$data[] = '3. ' . $this->mensa_menu_get($dataTmp, '<h2>Brainfood</h2>', $price_return);
		$data = implode('<br />', $data);
		//var_export($price);
		//return;
		$this->data = $data;

		// set date
		$this->date = $today;

		// get prices
		$price = array();
		// menu classic 1
		$posStart = strnposAfter($dataTmp, '<h2>Menü Classic 1</h2>', 0, 1);
		$posStart = strposAfter($dataTmp, '€', $posStart);
		$posEnd = strpos($dataTmp, '<div', $posStart);
		$data = mb_substr($dataTmp, $posStart, $posEnd - $posStart);
		$data = strip_tags($data);
		$price[] = cleanText($data);
		// menu classic 2
		$posStart = strnposAfter($dataTmp, '<h2>Menü Classic 2</h2>', 0, 1);
		$posStart = strposAfter($dataTmp, '€', $posStart);
		$posEnd = strpos($dataTmp, '<div', $posStart);
		$data = mb_substr($dataTmp, $posStart, $posEnd - $posStart);
		$data = strip_tags($data);
		$price[] = cleanText($data);
		// brainfood
		$price_return = str_replace_array(array('klein', 'groß', 'kl.', 'gr.'), '', $price_return);
		$price_return = explode('€', $price_return);
		foreach ($price_return as &$price_return_element) {
			$price_return_element = cleanText($price_return_element);
		}
		unset($price_return_element);
		$price_return = array_filter($price_return, function($var) { return !empty($var); });
		$price[] = $price_return;
		//var_export($price);
		//return;
		$this->price = $price;

		//var_export($data);
		//return;
		return $this->data;
	}

	public function parseDataSource_fallback() {
	}

	public function isDataUpToDate() {
		//return false;
		if ($this->date == date('d.m.', $this->timestamp))
			return true;
		else
			return false;
	}
}

?>