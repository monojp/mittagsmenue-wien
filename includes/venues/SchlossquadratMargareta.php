<?php

class SchlossquadratMargareta extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Margareta';
		$this->address = 'Margaretenplatz 2, 1050 Wien';
		$this->addressLat = '48.1916491';
		$this->addressLng = '16.3588115';
		$this->url = 'http://www.margareta.at/';
		$this->dataSource = 'http://www.margareta.at/pdf.php?days=23';
		$this->menu = 'http://www.margareta.at/fileadmin/files/margareta/pdf/KAMargareta141002.pdf';
		$this->statisticsKeyword = 'margareta';
		$this->no_menu_days = array(0);
		$this->lookaheadSafe = true;

		parent::__construct();
	}

	protected function get_today_variants() {
		$today_variants[] = getGermanDayName() . ' ' . date('j.n.', $this->timestamp);
		$today_variants[] = getGermanDayName() . ' ' . date('j.n', $this->timestamp);
		//$today_variants[] = date('j.n.', $this->timestamp);
		//$today_variants[] = date('j.n', $this->timestamp);
		return $today_variants;
	}

	protected function parseDataSource() {
		$dataTmp = pdftohtml($this->dataSource);
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
		$posEnd = mb_stripos($dataTmp, getGermanDayName(1), $posStart);
		// last day of the week
		if (!$posEnd)
			$posEnd = mb_stripos($dataTmp, 'br', $posStart);
		$data = mb_substr($dataTmp, $posStart, $posEnd-$posStart);
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
		//return error_log($data);

		$this->data = trim($data);

		// set date
		$this->date = $today;

		// set price
		$this->price = '5,60';

		//var_export($data);

		return $this->data;
	}
}
