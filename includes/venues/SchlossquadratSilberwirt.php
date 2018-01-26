<?php

class SchlossquadratSilberwirt extends SchlossquadratCommon {

	function __construct() {
		$this->title = 'Silberwirt';
		$this->address = 'Schloßgasse 21, 1050 Wien';
		$this->addressLat = 48.191094;
		$this->addressLng = 16.3593266;
		$this->url = 'http://www.silberwirt.at/';
		$this->dataSource = 'http://www.silberwirt.at/pdf.php?days=23';
		$this->no_menu_days = [ 0 ];
		$this->lookaheadSafe = true;

		$data = file_get_contents('http://www.silberwirt.at/essen-trinken.html');
		if (preg_match('/fileadmin.*KaSilber.*\.pdf/', $data, $matches) && count($matches)) {
			$this->menu = $this->url . $matches[0];
		}

		parent::__construct();
	}

}
