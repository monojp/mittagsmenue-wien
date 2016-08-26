<?php

class Duspara extends FoodGetterVenue {

	const FACEBOOK_ID = 926962184022734;

	function __construct() {
		$this->title = 'Duspara';
		$this->title_notifier = 'NEU';
		$this->address = 'Wiedner Hauptstraße 115, 1050 Wien';
		$this->addressLat = 48.184198;
		$this->addressLng = 16.361702;
		$this->url = 'http://www.duspara.at/';
		$this->dataSource = 'https://www.facebook.com/restaurantduspara';
		$this->menu = 'https://www.facebook.com/restaurantduspara';
		$this->statisticsKeyword = 'duspara';
		$this->no_menu_days = [ 0, 6 ];
		$this->lookaheadSafe = true;

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
		$all_posts = $fb_helper->get_all_picture_posts(self::FACEBOOK_ID);

		// use the first best picture with a keyword match in the message
		$object_id = null;
		foreach ((array)$all_posts as $post) {
			// get wanted data out of post
			$message = $post['message'];
			$object_id = $post['object_id'];

			// check message for keywords
			if (!stringsExist($message, [ 'Menüplan' ])) {
				continue;
			// we're done if we found one (because object_id was set already)
			} else {
				break;
			}
		}
		if (empty($object_id)) {
			return false;
		}

		//$this->menu = $fb_helper->get_graph_url_picture($object_id);
		$dataTmp = pdftotxt_ocr($fb_helper->get_picture_url($object_id));

		// check date range
		if (!$this->in_date_range_string($dataTmp, $this->timestamp, '%d.%m', '%d.%m')) {
			return;
		}

		$data = $this->parse_foods_inbetween_days($dataTmp, getGermanDayName(1), [ 'Solange' ]);

		/* get 2 weekday related prices out of all prices
		 * MO = 0, TUE = 1 => [0, 1], [2, 3] */
		$this->parse_foods_independant_from_days($dataTmp, "\n", $this->price, false, false);
		$weekday = date('w', $this->timestamp) - 1;
		$this->price = array_slice($this->price, $weekday * 2, 2);

		//return error_log(print_r($this->price, true));
		//return error_log($data);
		return ($this->data = $data);
	}

}
