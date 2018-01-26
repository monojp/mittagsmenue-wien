<?php

class SchlossquadratCuadro extends SchlossquadratCommon {

	function __construct() {
		$this->title = 'Cuadro';
		$this->address = 'Margaretenstrasse 77, 1050 Wien';
		$this->addressLat = 48.191312;
		$this->addressLng = 16.358338;
		$this->url = 'http://www.cuadro.at/';
		$this->dataSource = 'http://www.cuadro.at/pdf.php?days=23';
		$this->menu = 'http://www.cuadro.at/fileadmin/files/cuadro/pdf/KaCuadro_170501.pdf';
		$this->no_menu_days = [0, 6];
		$this->lookaheadSafe = true;

		parent::__construct();
	}

}
