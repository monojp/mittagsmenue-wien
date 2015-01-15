<?php

class NamNamDeli extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Nam Nam Deli';
		//$this->title_notifier = 'NEU';
		$this->address = 'Webgasse 3, 1060 Wien';
		$this->addressLat = '48.192375';
		$this->addressLng = '16.348016';
		$this->url = 'http://www.nam-nam.at/restaurant/';
		$this->dataSource = 'http://www.nam-nam.at/restaurant/wochenkarte/';
		$this->menu = 'http://www.nam-nam.at/restaurant/menue/speisen/';
		$this->statisticsKeyword = 'nam-nam';
		$this->no_menu_days = array(0, 1, 6);
		$this->lookaheadSafe = true;

		parent::__construct();
	}

	protected function get_today_variants() {
		$today_variants[] = getGermanDayName() . ', ' . date('j.', $this->timestamp) . ' ' . getGermanMonthName();
		$today_variants[] = getGermanDayName() . ', ' . date('d.', $this->timestamp) . ' ' . getGermanMonthName();
		if (date('n', $this->timestamp) == 1)
			$today_variants[] = getGermanDayName() . ', ' . date('d.', $this->timestamp) . ' Januar';
		return $today_variants;
	}

	protected function parseDataSource() {
		$dataTmp = file_get_contents($this->dataSource);
		if ($dataTmp === FALSE)
			return;
		//return error_log($dataTmp);

		if (stripos($dataTmp, 'urlaub') !== false)
			return ($this->data = VenueStateSpecial::Urlaub);

		// get menu data for the chosen day
		$today_variants = $this->get_today_variants();
		//return error_log(print_r($today, true));

		$today = null;
		foreach ($today_variants as $today) {
			$posStart = strposAfter($dataTmp, $today);
			if ($posStart !== false)
				break;
		}
		if ($posStart === false)
			return;

		$weekday = date('w', $this->timestamp);
		if ($weekday == 5) // friday
			$posEnd = mb_stripos($dataTmp, 'Daily Menu Specials', $posStart);
		else
			$posEnd = mb_stripos($dataTmp, getGermanDayName(1), $posStart);
		$data = mb_substr($dataTmp, $posStart, $posEnd-$posStart);
		//return error_log($data);
		$data = strip_tags($data, '<br>');
		// remove unwanted stuff
		$data = str_replace(array('&nbsp;'), '', $data);
		$data = str_ireplace(array("<br />","<br>","<br/>"), "\r\n", $data);
		// remove multiple newlines
		$data = preg_replace("/(\n)+/i", "\n", $data);
		$data = trim($data);
		// split per new line
		$foods = explode("\n", $data);
		//return error_log(print_r($foods, true));

		$data = null;
		$food_info_counter = 0; // 0 for title, 1 for first description, 2 for second, ..
		$food_counter = 1;
		foreach ($foods as $food) {
			$food = cleanText($food);
			if (!empty($food)) {
				// data empty, soup
				if (empty($data)) {
					$data = "$food\n";
				}
				// found new food, jump to new line
				else if ($food == 'oder') {
					$data .= "\n";
					$food_info_counter = 0;
					$food_counter++;
				}
				// found staff from before in new line only
				else {
					if ($food_info_counter == 0)
						$data .= "$food_counter. $food:";
					else if ($food_info_counter == 1)
						$data .= " $food";
					else
						$data .= ", $food";
					$food_info_counter++;
				}
			}
		}
		//return error_log($data);
		$data = str_replace("\n", "<br />", $data);
		$this->data = $data;

		// set date
		$this->date = $today;

		// set price
		$this->price = array('7,50');

		return $this->data;
	}

	public function parseDataSource_fallback() {
	}

	public function isDataUpToDate() {
		//return false;
		$today_variants = $this->get_today_variants();

		foreach ($today_variants as $today) {
			if ($this->date == $today)
				return true;
		}
		return false;
	}
}

?>
