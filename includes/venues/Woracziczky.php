<?php

require_once(__DIR__ . '/../FB_Helper.php');
require_once(__DIR__ . '/../includes.php');

class Woracziczky extends FoodGetterVenue {

	const FACEBOOK_ID = 350394680695;

	function __construct() {
		$this->title             = 'Woracziczky';
		//$this->title_notifier  = 'UPDATE';
		$this->address           = 'Spengergasse 52, 1050 Wien';
		$this->addressLat        = 48.189343;
		$this->addressLng        = 16.352982;
		$this->url               = 'http://www.woracziczky.at/';
		$this->dataSource        = 'https://facebook.com/WORACZICZKY';
		$this->menu              = 'https://facebook.com/WORACZICZKY/mediaset?album=pb.350394680695.-2207520000.1417430572.';
		$this->statisticsKeyword = 'woracziczky';
		$this->no_menu_days      = [ 0, 6 ];
		$this->lookaheadSafe     = true;

		parent::__construct();
	}

	protected function get_today_variants() {
		return [
			getGermanDayName()
		];
	}

	protected function parseDataSource() {
		global $fb_app_id, $fb_app_secret;
		$fb_helper = new FB_Helper($fb_app_id, $fb_app_secret);
		$all_posts = $fb_helper->get_all_posts(self::FACEBOOK_ID);

		$data = null;

		// set date
		$this->date = reset($this->get_today_variants());

		foreach ((array)$all_posts as $post) {
			if (!isset($post['message']) || !isset($post['created_time']) || !isset($post['from']))
				continue;

			// ignore user posts
			if ($post['from']['id'] != self::FACEBOOK_ID)
				continue;

			// get wanted data out of post
			$message      = $post['message'];
			$created_time = $post['created_time'];

			// clean/adapt data
			$message      = trim($message, "\n ");
			$created_time = strtotime($created_time);

			// not from today, skip
			if (date('Ymd', $created_time) != date('Ymd', $this->timestamp))
				continue;

			//error_log($message);

			// nothing mittags-relevantes found
			$words_relevant = [
				'Mittagspause', 'Mittagsmenü', 'was gibts', 'bringt euch', 'haben wir', 'gibt\'s', 'Essen', 'Mahlzeit',
				'Vorspeise', 'Hauptspeise', 'bieten euch', 'bis gleich', 'gibt', 'servieren euch', 'Bis gleich',
				'schmecken', 'freuen uns auf euch', 'neue Woche', 'wartet schon auf euch',
				'freuen sich auf euch', 'erwarten euch', 'suppe', 'Suppe', 'heute',
			];
			if (!stringsExist($message, $words_relevant))
				continue;

			// use whole fp post
			$data = str_replace("\n", "<br />", $message);
		}

		// all posts scanned, nothing found, but relevant string in a post => maybe vacation!
		if (empty($data) && $this->get_holiday_count(print_r($all_posts, true)))
			$data = VenueStateSpecial::UrlaubMaybe;

		$this->data = $data;

		// set price
		$this->price = [ 8.50 ];

		return $this->data;
	}

}
