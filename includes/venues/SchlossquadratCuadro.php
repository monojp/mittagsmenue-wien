<?php

class SchlossquadratCuadro extends SchlossquadratCommon {

	function __construct() {
		$this->title = 'Cuadro';
		$this->address = 'Margaretenstrasse 77, 1050 Wien';
		$this->addressLat = 48.191312;
		$this->addressLng = 16.358338;
		$this->url = 'http://www.cuadro.at/';
		$this->dataSource = 'http://www.cuadro.at/pdf.php?days=23';
		$this->no_menu_days = [0, 6];
		$this->lookaheadSafe = true;

		$data = file_get_contents('http://www.cuadro.at/essen-trinken.html');
		if (preg_match('/fileadmin.*KaCuadro.*\.pdf/', $data, $matches) && count($matches)) {
			$this->menu = $this->url . $matches[0];
		}

		parent::__construct();
	}

}
