<?php

class Stefan2 extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Stefan II';
		$this->title_notifier = 'NEU';
		$this->address = 'Hofmühlgasse 19, 1060 Wien';
		$this->addressLat = 48.194070;
		$this->addressLng = 16.351237;
		$this->url = 'http://www.stefan2.at/';
		$this->dataSource = 'http://www.stefan2.at/';
		$this->menu = 'http://www.stefan2.at/index.php/speisekarte/';
		$this->no_menu_days = [ 0, 6 ];
		$this->lookaheadSafe = true;

		parent::__construct();
	}

/*
 * structure
 * HEUTE (18.07.2016)</p>
 * <p><strong>Rindsuppe mit Profiterol</strong></p>
 * <p><strong style="line-height: 1.5;">Kalbsbraten mit Gemüse &#038; Bratkartoffeln><span style="line-height: 1.5;">€ 9,00</span><br />
 * <strong style="line-height: 1.5;">Bandnudeln mit Basilikumpesto &#038; Parmesan- </strong>€ 7,50</p>
 * <p>UNSER HEUTIGES 3-GÄNGE-MENÜ</p>
 * <p><strong>Risotto Frutti di Mare mit Parmesan</strong> &#8211; 9,00 €</p>
 * <p><strong>Branzinofilet gegrillt mit Mangolderdäpfeln> &#8211; 17,00 €</p>
 * <p><strong>Weißes Schokoladenmousse auf Erdbeerspiegel</strong>&#8211; 5,00 €</p>
 */

	protected function get_today_variants() {
		return [
			date('d.m.Y',  $this->timestamp),
		];
	}

	protected function parseDataSource() {
		$dataTmp = html_get_clean($this->dataSource);
		if (!$dataTmp ) {
			return;
		}
		//return error_log($dataTmp);

		// get menu data for the chosen day
		$day_next_or_end = [ 'UNSER HEUTIGES' ];
		$data = $this->parse_foods_inbetween_days($dataTmp, 'UNSER HEUTIGES',
				[ 'UNSER HEUTIGES' ]);
		//return error_log($data);
		if (!$data || is_numeric($data)) {
			return ($this->data = $data);
		}

		// get and set price
		$price = $this->parse_prices_regex($dataTmp, [ '/€ \d,(\d){2}/' ]);
		//return error_log(print_r($price, true));
		$this->price = $price;

		return ($this->data = $data);
	}

}
