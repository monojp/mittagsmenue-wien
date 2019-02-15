<?php

namespace App\Parser\Venue;

class SchlossquadratMargareta extends SchlossquadratCommon {

	function __construct() {
		$this->title = 'Margareta';
		$this->address = 'Margaretenplatz 2, 1050 Wien';
		$this->addressLat = 48.1916491;
		$this->addressLng = 16.3588115;
		$this->url = 'https://www.margareta.at/';
		$this->dataSource = 'https://www.margareta.at/pdf.php?days=23';
		$this->menu = 'https://www.margareta.at/essen-trinken.html';
		$this->no_menu_days = [ 0 ];
		$this->lookaheadSafe = true;

		parent::__construct();
	}

}
