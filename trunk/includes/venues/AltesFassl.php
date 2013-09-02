<?php

class AltesFassl extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Zum Alten Fassl';
		$this->address = 'Ziegelofengasse 37, 1050 Wien';
		$this->addressLat = '48.191498';
		$this->addressLng = '16.3604868';
		$this->url = 'http://www.zum-alten-fassl.at/';
		$this->dataSource = 'http://www.zum-alten-fassl.at/index.php/menues/mittagsmenues';
		$this->statisticsKeyword = 'zum-alten-fassl';
		$this->weekendMenu = 0;
		$this->lookaheadSafe = true;

		parent::__construct();
	}

	protected function parseDataSource() {
		$dataTmp = file_get_contents($this->dataSource);
		if ($dataTmp === FALSE)
			return;

		$today = date('d.m.', $this->timestamp);
		//$todayHotfix = '30.05.';
		$todayHotfix = $today;
		$posStart = striposAfter($dataTmp, $todayHotfix);
		if ($posStart === FALSE)
		{
			$today = date('d.m', $this->timestamp);
			$posStart = striposAfter($dataTmp, $today);
			if ($posStart === FALSE)
				return;
		}
		$tomorrow = getGermanDayName(1);
		$posEnd = stripos($dataTmp, $tomorrow, $posStart);
		// last day of the week
		if (!$posEnd)
			$posEnd = stripos($dataTmp, 'MENÜ 1', $posStart);
		$data = substr($dataTmp, $posStart, $posEnd-$posStart);
		$data = strip_tags($data);
		// remove unwanted stuff
		$data = html_entity_decode($data);
		$data = htmlentities($data);
		$data = str_replace(array('&nbsp;'), '', $data);
		$data = html_entity_decode($data);
		$data = str_replace(array('***', '1)', '2)', '3)'), '', $data);
		// remove multiple newlines
		$data = preg_replace("/([ ]*\n)+/i", "\n", $data);
		$data = trim($data);
		$foods = explode("\n", $data);
		// removes empty stuff in array
		for ($i = 0; $i<count($foods); $i++) {
			$foods[$i] = str_replace(array("\n", "\r"), '', $foods[$i]);
		}
		$foods = array_filter($foods, 'strlen');
		//var_export($foods);
		// set food
		$menu = array();
		$soup = null;
		if (count($foods) >= 3 && count($foods) <= 4) {
			$cnt = 0;
			foreach ($foods as $food) {
				if (!$soup)
					$soup = $food;
				else {
					$menu[$cnt++] = cleanText($food);
				}
			}
			$this->data = "$soup";
			$cnt = 1;
			foreach ($menu as $item) {
				$this->data .= "<br />$cnt. $item";
				$cnt++;
			}
		}

		// set date
		$this->date = $today;

		// set price
		$this->price = array('6,10', '7,30', '5,70');
		// adept if less than 3 menus found
		$this->price = array_slice($this->price, 0, count($foods) - 1);

		return $this->data;
	}

	public function parseDataSource_fallback() {
	}

	public function isDataUpToDate() {
		if ($this->date == date('d.m.', $this->timestamp) || $this->date == date('d.m', $this->timestamp))
			return true;
		else
			return false;
	}
}

?>
