<?php

class NamNamDeli extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Nam Nam Deli';
		$this->title_notifier = 'NEU';
		$this->address = 'Arbeitergasse 8, 1050 Wien';
		$this->addressLat = '48.186882';
		$this->addressLng = '16.353999';
		$this->url = 'http://www.nam-nam.at/';
		$this->dataSource = 'http://www.nam-nam.at/media/deli_Wochenkarte/wochenkarte_deli.pdf';
		$this->statisticsKeyword = 'nam-nam';
		$this->no_menu_days = array(0, 6);
		$this->lookaheadSafe = true;

		parent::__construct();
	}

	protected function parseDataSource() {
		$dataTmp = pdftohtml($this->dataSource);
		// remove unwanted stuff (fix broken htmlentities)
		$dataTmp = html_entity_decode($dataTmp);
		$dataTmp = htmlentities($dataTmp);
		$dataTmp = str_replace(array('&nbsp;'), ' ', $dataTmp);
		$dataTmp = preg_replace('/[[:blank:]]+/', ' ', $dataTmp);
		$dataTmp = html_entity_decode($dataTmp);
		//var_export($dataTmp);
		//return;

		// date without trailing zeros
		$today = getGermanDayName() . ', ' . date('j.n.', $this->timestamp);
		$posStart = striposAfter($dataTmp, $today);
		// date with trailing zeros
		if ($posStart === false) {
			$today = getGermanDayName() . ', ' . date('d.m.', $this->timestamp);
			$posStart = striposAfter($dataTmp, $today);
			if ($posStart === false)
				return;
		}
		$weekday = date('w', $this->timestamp);
		if ($weekday == 3) // wednesday
			$posEnd = stripos($dataTmp, 'Wochenkarte', $posStart);
		else if ($weekday == 5) // friday
			$posEnd = stripos($dataTmp, 'Pikant bis sehr scharf', $posStart);
		else
			$posEnd = stripos($dataTmp, getGermanDayName(1), $posStart);
		$data = substr($dataTmp, $posStart, $posEnd-$posStart);
		$data = strip_tags($data, '<br>');
		// remove unwanted stuff
		$data = str_replace(array('&nbsp;'), '', $data);
		$data = str_ireplace(array("<br />","<br>","<br/>"), "\r\n", $data);
		// remove multiple newlines
		$data = preg_replace("/(\n)+/i", "\n", $data);
		$data = trim($data);
		// split per new line
		$foods = explode("\n", $data);
		//var_export($foods);
		//return;

		$data = null;
		$food_info_counter = 0; // 0 for title, 1 for first description, 2 for second, ..
		$food_counter = 1;
		foreach ($foods as $food) {
			$food = cleanText($food);
			if (!empty($food)) {
				// found new food, jump to new line
				if ($food == 'oder') {
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
		//var_export($data);
		//return;
		$data = str_replace("\n", "<br />", $data);
		$this->data = $data;

		// set date
		$this->date = $today;

		// set price
		$this->price = array('7,50');

		//var_export($data);
		return $this->data;
	}

	public function parseDataSource_fallback() {
	}

	public function isDataUpToDate() {
		//return false;
		if (
			$this->date == getGermanDayName() . ', ' . date('j.n.', $this->timestamp) ||
			$this->date == getGermanDayName() . ', ' . date('d.m.', $this->timestamp)
		)
			return true;
		else
			return false;
	}
}

?>
