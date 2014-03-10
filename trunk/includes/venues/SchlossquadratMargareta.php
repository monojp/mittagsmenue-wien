<?php

class SchlossquadratMargareta extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Margareta';
		$this->address = 'Margaretenplatz 2, 1050 Wien';
		$this->addressLat = '48.1916491';
		$this->addressLng = '16.3588115';
		$this->url = 'http://www.margareta.at/';
		$this->dataSource = 'http://www.margareta.at/pdf.php?days=23';
		$this->statisticsKeyword = 'margareta';
		$this->no_menu_days = array(0);
		$this->lookaheadSafe = true;

		parent::__construct();
	}

	protected function parseDataSource() {
		/*$dataTmp = file_get_contents($this->dataSource);
		if ($dataTmp === FALSE)
			return;*/
		$dataTmp = pdftohtml($this->dataSource);
		$dataTmp = preg_replace('/[[:blank:]]+/', ' ', $dataTmp);
		//var_export($dataTmp);

		$today = getGermanDayName() . ' ' . date('j.n.', $this->timestamp);
		$tomorrow = getGermanDayName(1);
		$posStart = striposAfter($dataTmp, $today);
		if ($posStart === FALSE) {
			return;
		}
		$posEnd = stripos($dataTmp, $tomorrow, $posStart);
		// last day of the week
		if (!$posEnd)
			$posEnd = stripos($dataTmp, 'br', $posStart);
		$data = substr($dataTmp, $posStart, $posEnd-$posStart);
		//var_export($data);
		$data = strip_tags($data);
		// remove unwanted stuff
		$data = str_replace(array('***', '1)', '2)', '3)'), '', $data);
		$data = str_replace('Powered by TCPDF (www.tcpdf.org)', '', $data);
		// remove multiple newlines
		$data = preg_replace("/([ ]*\n)+/i", "\n", $data);

		// clean text and add seperator for 2nd line
		$data = cleanText($data);
		$data = preg_replace("/[\r\n]{1,}/", " & ", $data);

		$this->data = trim($data);

		// set date
		$this->date = $today;

		// set price
		$this->price = '5,20';

		//var_export($data);

		return $this->data;
	}

	public function parseDataSource_fallback() {
		$dataSourceTmp = $this->url;
		$dataTmp = file_get_contents($dataSourceTmp);
		if ($dataTmp === FALSE)
			return;

		$posStart = striposAfter($dataTmp, '<p class="tickertext">');
		if ($posStart === FALSE)
			return;
		$posEnd = stripos($dataTmp, '</p>', $posStart);
		$data = cleanText(substr($dataTmp, $posStart, $posEnd-$posStart));

		// set date
		$parts = explode(' ', $data);
		$this->date = trim($parts[1], ':.');
		unset($parts[0]);
		unset($parts[1]);
		$this->data = implode(' ', $parts);

		// set price
		if (strpos($this->data, 'Feiertagsmenü') === FALSE) {
			$posStart = striposAfter($dataTmp, 'Mittagsteller um');
			$posEnd = stripos($dataTmp, '€', $posStart);
			$data = substr($dataTmp, $posStart, $posEnd-$posStart);
			$this->price = trim($data);
		}

		return $this->data;
	}

	public function isDataUpToDate() {
		$today = getGermanDayName() . ' ' . date('j.n.', $this->timestamp);
		if ($this->date == $today || $this->date == date('j.n.', $this->timestamp) || $this->date == date('j.n', $this->timestamp))
			return true;
		else
			return false;
	}
}

?>
