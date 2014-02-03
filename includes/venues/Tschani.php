<?php

class Tschani extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Gasthaus T\'schani';
		$this->title_notifier = 'NEU';
		$this->address = 'Marchettigasse 1, 1060 Wien';
		$this->addressLat = '48.191212';
		$this->addressLng = '16.351033';
		$this->url = 'http://www.gasthaus-tschani.at/';
		$this->dataSource = 'http://www.gasthaus-tschani.at/cms/pages/wochenmenueplan.php';
		$this->statisticsKeyword = 'gasthaus-tschani';
		$this->weekendMenu = 0;
		$this->lookaheadSafe = true;

		parent::__construct();
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

		// check if current day is in menu range
		$rangeStart = strposAfter($dataTmp, 'vom');
		$rangeStop = strpos($dataTmp, '-', $rangeStart);
		if (!$rangeStart || !$rangeStop)
			return;
		$range_date_start = substr($dataTmp, $rangeStart, $rangeStop - $rangeStart);
		$range_date_start = trim($range_date_start, '. ');
		$range_date_start = strtotime($range_date_start);
		$rangeStart2 = strposAfter($dataTmp, '-', $rangeStop);
		$rangeStop2 = strpos($dataTmp, '<', $rangeStart2);
		if (!$rangeStart2 || !$rangeStop2)
			return;
		$range_date_end = substr($dataTmp, $rangeStart2, $rangeStop2 - $rangeStart2);
		$range_date_end = trim($range_date_end, '. ');
		$range_date_end = strtotime($range_date_end);
		$range_date_end = strtotime('+1 days', $range_date_end);
		if (
			($this->timestamp >= $range_date_start && $this->timestamp <= $range_date_end) ||
			($this->timestamp <= $range_date_start && $this->timestamp <= $range_date_end)
		) {
			// all fine
		}
		else
			return;

		$posStart = striposAfter($dataTmp, getGermanDayName());
		if ($posStart === FALSE)
			return;
		$posEnd = stripos($dataTmp, getGermanDayName(1), $posStart);
		// friday
		if (!$posEnd) {
			$posEnd = stripos($dataTmp, '</div>', $posStart);
			if (!$posEnd)
				return;
		}
		$data = substr($dataTmp, $posStart, $posEnd - $posStart);
		$data = strip_tags($data, '<br>');
		//error_log(print_r($data, true));
		//return;
		// remove unwanted stuff
		$data = str_replace(array('&nbsp;'), '', $data);
		$data = str_ireplace(array("<br />","<br>","<br/>"), "\r\n", $data);
		$data = preg_replace("/([a-z])\n([a-z])/i", '$1 $2', $data);
		// remove multiple newlines
		$data = preg_replace("/(\n)+/i", "\n", $data);
		// remove price (e.g "...6,60 EUR") if set
		$data = preg_replace('/[.]*[0-9]{1,2}[,][0-9]{1,2}.(EUR)*/', '', $data);
		$data = trim($data);
		//error_log(print_r($data, true));
		//return;
		// split per new line
		$foods = explode("\n", $data);

		$data = null;
		$cnt = 0;
		foreach ($foods as $food) {
			$food = cleanText($food);
			if (!empty($food)) {
				// starter
				if (!$data)
					$data = $food;
				else {
					$cnt++;
					$data .= "\n$cnt. $food";
				}
			}
		}
		$data = cleanText($data);
		$this->data = $data;
		//error_log($data);
		//return;

		// set price
		$this->price = array('6.00');
		//return;

		// set date
		$this->date = getGermanDayName();

		return $this->data;
	}

	public function parseDataSource_fallback() {
	}

	public function isDataUpToDate() {
		if ($this->date == getGermanDayName())
			return true;
		else
			return false;
	}
}

?>
