<?php

class Ausklang extends FoodGetterVenue {

	function __construct() {
		$this->title = 'aus:klang';
		$this->address = 'Kleistgasse 1, 1030 Wien';
		$this->addressLat = '48.1931822';
		$this->addressLng = '16.3906608';
		$this->url = 'http://ausklang.at/';
		$this->dataSource = 'http://ausklang.at/essen/menue.html';
		$this->statisticsKeyword = 'ausklang';
		$this->no_menu_days = array(0, 6);
		$this->lookaheadSafe = true;
		$this->price_nested_info = 'Vorspeise oder Dessert / Vorspeise und Dessert';

		parent::__construct();
	}

	protected function parseDataSource() {
		$dataTmp = file_get_contents($this->dataSource);
		if ($dataTmp === FALSE)
			return;

		if (stripos($dataTmp, 'urlaub') !== false)
			return ($this->data = VenueStateSpecial::Urlaub);

		$today = mb_strtolower(getGermanDayName()) . ', ' . date('d.', $this->timestamp) . ' ' . mb_strtolower(getGermanMonthName());
		$posStart = strposAfter($dataTmp, $today);
		if ($posStart === FALSE) {
			/*$today = date('j.n', $this->timestamp);
			$posStart = striposAfter($dataTmp, $today);
			if ($posStart === FALSE)
				return;*/
			return;
		}
		$posEnd = mb_stripos($dataTmp, getGermanDayName(1), $posStart);
		// last day of the week
		if (!$posEnd)
			$posEnd = mb_strpos($dataTmp, 'menü', $posStart);
		$data = mb_substr($dataTmp, $posStart, $posEnd-$posStart);
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
		$this->price = array(array('6,90', '8,90'));

		//var_export($data);
		//echo $data;
		return $this->data;
	}

	public function parseDataSource_fallback() {
	}

	public function isDataUpToDate() {
		//return false;
		$today = mb_strtolower(getGermanDayName()) . ', ' . date('d.', $this->timestamp) . ' ' . mb_strtolower(getGermanMonthName());

		if ($this->date == $today)
			return true;
		else
			return false;
	}
}

?>