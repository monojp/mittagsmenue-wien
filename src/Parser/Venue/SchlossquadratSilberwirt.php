<?php

namespace App\Parser\Venue;

class SchlossquadratSilberwirt extends SchlossquadratCommon {

	function __construct() {
		$this->title = 'Silberwirt';
		$this->address = 'Schloßgasse 21, 1050 Wien';
		$this->addressLat = 48.191094;
		$this->addressLng = 16.3593266;
		$this->url = 'https://www.silberwirt.at/';
		$this->dataSource = 'https://www.silberwirt.at/pdf.php?days=23';
		$this->menu = 'https://www.silberwirt.at/essen-trinken.html';
		$this->no_menu_days = [ 0 ];
		$this->lookaheadSafe = true;

		parent::__construct();
	}

}
