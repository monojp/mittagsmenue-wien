<?php

class HaasBeisl extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Haas Beisl';
		$this->address = 'Margaretenstraße 74, 1050 Wien';
		$this->addressLat = '48.1925285';
		$this->addressLng = '16.3602763';
		$this->url = 'http://www.haasbeisl.at/';
		$this->dataSource = 'http://www.haasbeisl.at/pdf/aktuelles.pdf';
		$this->statisticsKeyword = 'haasbeisl';
		$this->weekendMenu = 0;
		$this->lookaheadSafe = true;

		parent::__construct();
	}

	protected function parseDataSource() {
		/*$dataTmp = file_get_contents($this->dataSource);
		if ($dataTmp === FALSE)
			return;*/
		$dataTmp = pdftohtml($this->dataSource);
		// remove unwanted stuff (fix broken htmlentities)
		$dataTmp = html_entity_decode($dataTmp);
		$dataTmp = htmlentities($dataTmp);
		$dataTmp = str_replace(array('&nbsp;'), ' ', $dataTmp);
		$dataTmp = preg_replace('/[[:blank:]]+/', ' ', $dataTmp);
		$dataTmp = html_entity_decode($dataTmp);

		$today = getGermanDayName() . ', ' . date('j.', $this->timestamp) . ' ' . getGermanMonthName();
		$posStart = strposAfter($dataTmp, $today);
		if ($posStart === FALSE)
			return;
		$friday = (date('w', $this->timestamp) == 5);
		if (!$friday)
			$posEnd = stripos($dataTmp, getGermanDayName(1), $posStart);
		else
			$posEnd = strpos($dataTmp, 'Menüsalat', $posStart);
		$data = substr($dataTmp, $posStart, $posEnd-$posStart);
		$data = html_entity_decode($data);
		$data = strip_tags($data, '<br>');
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
		foreach ($foods as $food) {
			$food = cleanText($food);
			if (!empty($food)) {
				if (!$data) {
					// starter
					$data = $food;
				}
				else {
					// checker for empty entries in pdf
					$foodWithoutNumber = cleanText(preg_replace("/[0-9]{1,1}\./", '', $food));
					if (empty($foodWithoutNumber))
						break;
					// found new food
					if (preg_match("/[0-9]{1,1}/", $food)) {
						$data .= "\n" . $food;
					}
					// found staff from before in new line only
					else {
						$data .= ' ' . $food;
					}
				}
			}
			else
				break;
		}
		//var_export($data);
		//return;
		$data = str_replace("\n", "<br />", $data);
		$this->data = $data;

		// set date
		$this->date = $today;

		// set price
		$this->price = array('7,90', '6,10');

		//var_export($data);
		return $this->data;
	}

	public function parseDataSource_fallback() {
	}

	public function isDataUpToDate() {
		//return false;
		$today = getGermanDayName() . ', ' . date('j.', $this->timestamp) . ' ' . getGermanMonthName();

		if ($this->date == $today)
			return true;
		else
			return false;
	}
}

?>
