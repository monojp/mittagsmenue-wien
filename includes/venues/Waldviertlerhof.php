<?php

class Waldviertlerhof extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Waldviertlerhof';
		//$this->title_notifier = 'NEU';
		$this->address = 'Schönbrunnerstrasse 20, 1050 Wien';
		$this->addressLat = '48.193692';
		$this->addressLng = '16.358687';
		$this->url = 'http://www.waldviertlerhof.at/';
		$this->dataSource = 'http://www.waldviertlerhof.at/htm/wochenmenue.doc';
		$this->statisticsKeyword = 'waldviertlerhof';
		$this->no_menu_days = array(0, 6);
		$this->lookaheadSafe = true;

		parent::__construct();
	}

	protected function parseDataSource() {
		$dataTmp = doctotxt($this->dataSource);
		$dataTmp = preg_replace('/[[:blank:]]+/', ' ', $dataTmp);
		//error_log($dataTmp);
		//return;

		if (stripos($dataTmp, 'urlaub') !== false)
			return ($this->data = VenueStateSpecial::Urlaub);

		// check if current week number matches
		$week_number = date('W', $this->timestamp);
		preg_match('/[0-9]{2}[\. ]+KW/', $dataTmp, $matches);
		if (empty($matches))
			return;
		$week_number_match = trim($matches[0], 'KW. ');
		if ($week_number != $week_number_match)
			return;

		$today = getGermanDayName();
		$tomorrow = getGermanDayName(1);
		$posStart = striposAfter($dataTmp, $today);
		if ($posStart === FALSE) {
			return;
		}
		$posEnd = mb_stripos($dataTmp, $tomorrow, $posStart);
		// last day of the week
		if (!$posEnd)
			$posEnd = mb_stripos($dataTmp, 'Ihre Tischreservierung', $posStart);
		$dataTmp = mb_substr($dataTmp, $posStart, $posEnd-$posStart);
		$dataTmp = explode('|', $dataTmp);
		//var_export($dataTmp);
		//return;

		$data = null;
		foreach ($dataTmp as $food) {
			$food = cleanText($food);
			if (empty($food))
				continue;
			// price
			if (preg_match('/^[0-9]+,[0-9]+$/', $food))
				$this->price = $food;
			// food part
			else
				$data = empty($data) ? $food : "$data $food";
		}

		$this->data = $data;

		// set date
		$this->date = $week_number . $today;

		//var_export($data);
		//return;
		return $this->data;
	}

	public function parseDataSource_fallback() {
	}

	public function isDataUpToDate() {
		if ($this->date == date('W', $this->timestamp) . getGermanDayName())
			return true;
		else
			return false;
	}
}

?>