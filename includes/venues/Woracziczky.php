<?php

require_once(__DIR__ . '/../FB_Helper.php');
require_once(__DIR__ . '/../includes.php');

class Woracziczky extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Gasthaus Woracziczky';
		$this->title_notifier = 'NEU';
		$this->address = 'Spengergasse 52, 1050 Wien';
		$this->addressLat = '48.189343';
		$this->addressLng = '16.352982';
		$this->url = 'http://www.woracziczky.at/';
		$this->dataSource = 'https://facebook.com/WORACZICZKY';
		$this->statisticsKeyword = 'woracziczky';
		$this->no_menu_days = array(0, 6);
		$this->lookaheadSafe = true;

		parent::__construct();
	}

	protected function parseDataSource() {
		global $fb_app_id, $fb_app_secret;
		$fb_helper = new FB_Helper($fb_app_id, $fb_app_secret);
		$all_posts = $fb_helper->get_all_posts('woracziczky');

		$data = $today = null;

		foreach ((array)$all_posts  as $post) {
			if (!isset($post['message']) || !isset($post['created_time']))
				continue;

			// get wanted data out of post
			$message = $post['message'];
			$created_time = $post['created_time'];

			// nothing mittags-relevantes found
			if (!stringsExist($message, array('Mittagspause', 'Mittagsmenü', 'was gibts', 'bringt euch')))
				continue;

			// clean/adapt data
			$message = trim($message, "\n ");
			$created_time = strtotime($created_time);

			// not from today, skip
			if (date('Ymd', $created_time) != date('Ymd', $this->timestamp))
				continue;

			/* try form
			 * <random text>
			 * <lunch info>
			 * <random text>
			 */
			preg_match_all('/\n.+\n/', $message, $matches);

			/* try alternative form
				* <lunch info> das ist unser Mittagsmenü!
				*/
			if (empty($matches[0])) {
				preg_match('/.*(das ist unser Mittagsmenü)/', $message, $matches);
				$matches[0] = isset($matches[0]) ? array(str_replace('das ist unser Mittagsmenü', '', $matches[0])) : null;

				/* try alternative form
					 * <lunch info> sind heute unser Mittagsmenü
					 */
				if (empty($matches[0])) {
					preg_match('/.*(sind heute unser Mittagsmenü)/', $message, $matches);
					$matches[0] = isset($matches[0]) ? array(str_replace('sind heute unser Mittagsmenü', '', $matches[0])) : null;

					// still nothing found, skip
					if (empty($matches[0]))
						continue;
				}
			}

			// get longest match which also contains a keyword
			$longest_length = 0;
			$longest_key = 0;
			foreach ($matches[0] as $key => $match) {
				$current_length = strlen($match);
				if (
					$current_length > $longest_length &&
					(
						strpos($match, 'auf') !== FALSE ||
						strpos($match, 'mit') !== FALSE ||
						strpos($match, 'und') !== FALSE ||
						strpos($match, 'in') !== FALSE
					)
				) {
					$longest_length = $current_length;
					$longest_key = $key;
				}
			}

			$mittagsmenue = $matches[0][$longest_key];
			$mittagsmenue = trim($mittagsmenue, "\n!;-) ");

			// strip unwanted words from the beginning
			if (stripos($mittagsmenue, 'eine') == 0)
				$mittagsmenue = substr($mittagsmenue, striposAfter($mittagsmenue, 'eine'));

			$data = $mittagsmenue;
			$today = getGermanDayName();
		}

		$this->data = $data;

		// set price
		$this->price = null;

		// set date
		$this->date = $today;

		return $this->data;
	}

	public function parseDataSource_fallback() {
	}

	public function isDataUpToDate() {
		if ($this->date == getGermanDayName())
			return true;
		else
			return false;
	}
}

?>