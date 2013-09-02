<?php

// UPDATE REQUIRED

class Salzberg extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Salzberg';
		$this->address = 'Magdalenenstraße 17, 1060 Wien';
		$this->addressLat = '48.1954193';
		$this->addressLng = '16.3534503';
		$this->url = 'http://www.salzberg.at/';
		$this->dataSource = 'http://www.salzberg.at/mittagskarte.pdf';
		$this->statisticsKeyword = 'salzberg';
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
		//var_export($dataTmp);
		//return;

		$today = getGermanDayName() . ', ' . date('j.', $this->timestamp) . ' ' . getGermanMonthName() . ' ' . date('Y', $this->timestamp);
		$posStart = strposAfter($dataTmp, $today);
		if ($posStart === FALSE)
		{
			$today = getGermanDayName() . ', ' . date('d.', $this->timestamp) . ' ' . getGermanMonthName() . ' ' . date('Y', $this->timestamp);
			$posStart = striposAfter($dataTmp, $today);
			if ($posStart === FALSE)
				return;
		}
		$posEnd = stripos($dataTmp, getGermanDayName(1), $posStart);
		// last day of the week
		if (!$posEnd)
			$posEnd = mb_strpos($dataTmp, 'Mittagessen', $posStart);
		if (!$posEnd)
			$posEnd = mb_strpos($dataTmp, 'Werktags', $posStart);
		$data = substr($dataTmp, $posStart, $posEnd-$posStart);
		$data = html_entity_decode($data);
		$data = strip_tags($data, '<br>');
		// remove unwanted stuff
		$data = str_replace(array('&nbsp;'), '', $data);
		$data = str_ireplace(array("<br />","<br>","<br/>"), "\r\n", $data);
		$data = preg_replace("/([a-z])\n([a-z])/i", '$1 $2', $data);
		$data = str_replace(array('Menü', 'Tagesteller', 'Alt', '∞'), '', $data);
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
		$numberPrinted = false;
		foreach ($foods as $food) {
			$food = cleanText($food);
			if (!empty($food)) {
				if (!$numberPrinted) {
					$data .= "$cnt. $food\n";
					$numberPrinted = true;
				}
				else {
					$data .= "$food\n";
				}
			}
			else {
				$cnt++;
				$numberPrinted = false;
			}
		}
		//var_export($data);
		//return;
		$data = str_replace("\n", "<br />", $data);
		$this->data = $data;

		// set date
		$this->date = $today;

		// set price
		$this->price = array(array('6,90', '8,90'), '5,80');

		//var_export($data);
		//echo $data;
		return $this->data;
	}

	public function parseDataSource_fallback() {
	}

	public function isDataUpToDate() {
		//return false;
		$today = getGermanDayName() . ', ' . date('j.', $this->timestamp) . ' ' . getGermanMonthName() . ' ' . date('Y', $this->timestamp);
		$today_alt = getGermanDayName() . ', ' . date('d.', $this->timestamp) . ' ' . getGermanMonthName() . ' ' . date('Y', $this->timestamp);

		if ($this->date == $today || $this->date == $today_alt)
			return true;
		else
			return false;
	}
}

?>
