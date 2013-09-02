<?php

class Kunsthallencafe extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Kunsthallencafé';
		$this->address = 'Treitlstraße 2, 1040 Wien';
		$this->addressLat = '48.199556';
		$this->addressLng = '16.368233';
		$this->url = 'http://www.kunsthallencafe.at/';
		$this->dataSource = 'http://www.kunsthallencafe.at/khc/uploads/wochenkarte.pdf';
		$this->statisticsKeyword = 'kunsthallencafe';
		$this->weekendMenu = 0;
		$this->lookaheadSafe = true;

		parent::__construct();
	}

	protected function parseDataSource() {
		/*$dataTmp = file_get_contents($this->dataSource);
		if ($dataTmp === FALSE)
			return;*/
		$dataTmp = pdftohtml($this->dataSource);
		//var_export($dataTmp);
		//return;
		// remove unwanted stuff (fix broken htmlentities)
		$dataTmp = html_entity_decode($dataTmp);
		$dataTmp = htmlentities($dataTmp);
		$dataTmp = str_replace(array('&nbsp;'), ' ', $dataTmp);
		$dataTmp = html_entity_decode($dataTmp);

		// check day
		$today = getGermanDayName() . ' ' . date('d.m.Y', $this->timestamp);
		$posStart = strposAfter($dataTmp, $today);
		if ($posStart === FALSE) {
			/*$today = date('j.n', $this->timestamp);
			$posStart = striposAfter($dataTmp, $today);
			if ($posStart === FALSE)
				return;*/
			return;
		}
		$posEnd = stripos($dataTmp, getGermanDayName(1), $posStart);
		// last day of the week
		if (!$posEnd)
			//$posEnd = stripos($dataTmp, '* alle Preise', $posStart);
			$data = substr($dataTmp, $posStart/*, $posEnd-$posStart*/);
		else
			$data = substr($dataTmp, $posStart, $posEnd-$posStart);
		$data = strip_tags($data, '<br>');
		//var_export($data);
		//return;
		// remove unwanted stuff
		$data = str_replace(array('&nbsp;'), '', $data);
		$data = str_ireplace(array("<br />","<br>","<br/>"), "\r\n", $data);
		$data = preg_replace("/([a-z])\n([a-z])/i", '$1 $2', $data);
		// remove multiple newlines
		$data = preg_replace("/(\n)+/i", "\n", $data);
		$data = trim($data);
		//var_export($data);
		// split per new line
		$foods = explode("\n", $data);
		//var_export($foods);
		//return;

		$data = null;
		$cnt = 1;
		foreach ($foods as $food) {
			$food = cleanText($food);
			if (!empty($food)) {
				if (!$data) {
						$data = $food;
				}
				else {
					$data .= "\n$cnt. " . $food;
					$cnt++;
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
		$this->price = array('8.50', '9.50');
		//var_export($price);
		//return;

		//var_export($data);
		//return;
		//echo $data;
		return $this->data;
	}

	public function parseDataSource_fallback() {
	}

	public function isDataUpToDate() {
		//return false;
		$today = getGermanDayName() . ' ' . date('d.m.Y', $this->timestamp);

		if ($this->date == $today)
			return true;
		else
			return false;
	}
}

?>
