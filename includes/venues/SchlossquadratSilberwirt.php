<?php

class SchlossquadratSilberwirt extends SchlossquadratCommon {

	function __construct() {
		$this->title = 'Silberwirt';
		$this->address = 'Schloßgasse 21, 1050 Wien';
		$this->addressLat = 48.191094;
		$this->addressLng = 16.3593266;
		$this->url = 'http://www.silberwirt.at/';
		$this->dataSource = 'http://www.silberwirt.at/pdf.php?days=23';
		$this->menu = 'http://www.silberwirt.at/fileadmin/files/silberwirt/pdf/KaSilber161001.pdf';
		$this->no_menu_days = [ 0 ];
		$this->lookaheadSafe = true;

		parent::__construct();
	}

}
