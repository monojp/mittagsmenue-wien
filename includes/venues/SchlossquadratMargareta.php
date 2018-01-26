<?php

class SchlossquadratMargareta extends SchlossquadratCommon {

	function __construct() {
		$this->title = 'Margareta';
		$this->address = 'Margaretenplatz 2, 1050 Wien';
		$this->addressLat = 48.1916491;
		$this->addressLng = 16.3588115;
		$this->url = 'http://www.margareta.at/';
		$this->dataSource = 'http://www.margareta.at/pdf.php?days=23';
		$this->no_menu_days = [ 0 ];
		$this->lookaheadSafe = true;

		$data = file_get_contents('http://www.margareta.at/essen-trinken.html');
		if (preg_match('/fileadmin.*KAMargareta.*\.pdf/', $data, $matches) && count($matches)) {
			$this->menu = $this->url . $matches[0];
		}

		parent::__construct();
	}

}
