<?php

class Stoeger extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Restaurant Stöger';
		//$this->title_notifier = 'NEU';
		$this->address = 'Ramperstorffergasse 63, 1050 Wien';
		$this->addressLat = '48.190359';
		$this->addressLng = '16.354808';
		$this->url = 'http://www.zumstoeger.at';
		$this->dataSource = 'http://www.zumstoeger.at/heute.php';
		$this->statisticsKeyword = 'zumstoeger';
		$this->weekendMenu = 0;
		$this->lookaheadSafe = true;

		parent::__construct();
	}

	private function alternative_date_fix($date) {
		$date = str_replace('Donnerstag', 'Donnerstage', $date);
		$date = str_replace('Mittwoch,', 'Mitwoch', $date);
		$date = str_replace('Mittwoch', 'Mitwoch', $date);
		return $date;
	}

	protected function parseDataSource() {
		$dataTmp = file_get_contents($this->dataSource);
		if ($dataTmp === FALSE)
			return;
		//error_log(print_r($dataTmp, true));
		//return;
		// remove unwanted stuff (fix broken htmlentities)
		$dataTmp = html_entity_decode($dataTmp);
		$dataTmp = htmlentities($dataTmp);
		$dataTmp = str_replace(array('&nbsp;'), ' ', $dataTmp);
		$dataTmp = html_entity_decode($dataTmp);

		// vorspeise
		$today = getGermanDayName() . ' ' . date('j.', $this->timestamp) . getGermanMonthName() . ' ' . date('Y', $this->timestamp);
		$posStart = strposAfter($dataTmp, $today);
		if ($posStart === FALSE) {
			$today = getGermanDayName() . ', ' . date('j.', $this->timestamp) . getGermanMonthName() . ' ' . date('Y', $this->timestamp);
			$posStart = strposAfter($dataTmp, $today);
			if ($posStart === FALSE) {
				$today = $this->alternative_date_fix($today);
				$posStart = strposAfter($dataTmp, $today);
				if ($posStart === FALSE)
					return;
			}
		}
		$posEnd = stripos($dataTmp, '</p>', $posStart);
		if (!$posEnd)
			return;
		$starter = substr($dataTmp, $posStart, $posEnd - $posStart);
		$starter = strip_tags($starter, '<br>');
		//error_log(print_r($data, true));
		//return;
		// remove unwanted stuff
		$starter = str_replace(array('&nbsp;'), '', $starter);
		$starter = str_ireplace(array("<br />","<br>","<br/>"), "\r\n", $starter);
		$starter = preg_replace("/([a-z])\n([a-z])/i", '$1 $2', $starter);
		// remove multiple newlines
		$starter = preg_replace("/(\n)+/i", "\n", $starter);
		$starter = trim($starter);
		//error_log(print_r($starter, true));
		//return;

		// hauptspeise
		$posStart = $posEnd;
		$posStart = strposAfter($dataTmp, '>', $posEnd);
		if (!$posStart)
			return;
		$posEnd = stripos($dataTmp, '<hr', $posStart);
		if (!$posEnd)
			return;
		$main = substr($dataTmp, $posStart, $posEnd - $posStart);
		$main = strip_tags($main, '<br>');
		//error_log(print_r($main, true));
		//return;
		// remove unwanted stuff
		$main = str_replace(array('&nbsp;'), '', $main);
		$main = str_ireplace(array("<br />","<br>","<br/>"), "\r\n", $main);
		$main = preg_replace("/([a-z])\n([a-z])/i", '$1 $2', $main);
		// remove multiple newlines
		$main = preg_replace("/(\n)+/i", "\n", $main);
		$main = trim($main);
		//error_log(print_r($main, true));
		//return;

		// get prices out of main via regex
		preg_match_all('/[0-9]+,[0-9]+/', $main, $prices);
		//error_log(print_r($prices[0], true));
		//return;
		$this->price = isset($prices[0]) ? $prices[0] : null;

		// cleanup main
		$main = preg_replace('/[0-9]+,[0-9]+/', '', $main);
		$main = str_replace_array(array('€', 'á'), '', $main);
		$main = trim($main);
		//error_log(print_r($main, true));
		//return;

		$data = "$starter";
		$main = explode('oder', $main);
		foreach ($main as $index => $food) {
			$food = cleanText($food);
			$data .= "\n" . ($index + 1) . ". $food";
		}
		$data = str_replace("\n", "<br />", $data);
		//error_log(print_r($data, true));
		//return;
		$this->data = $data;

		// set date
		$this->date = $today;

		return $this->data;
	}

	public function parseDataSource_fallback() {
	}

	public function isDataUpToDate() {
		//return false;
		$date_check = str_replace(',', '', $this->date);
		$today = getGermanDayName() . ' ' . date('j.', $this->timestamp) . getGermanMonthName() . ' ' . date('Y', $this->timestamp);

		if ($date_check == $today)
			return true;
		else if ($date_check == $this->alternative_date_fix($today))
			return true;
		else
			return false;
	}
}

?>