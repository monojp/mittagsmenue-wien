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
			preg_match_all('/\n\n.+\n\n/', $message, $matches);

			$alternative_regexes = array(
				array(
					'regex' => '/.*(das ist)/',
					'strip' => 'das ist',
				),
				array(
					'regex' => '/.*(sind heute)/',
					'strip' => 'sind heute',
				),
				array(
					'regex' => '/.*(das alles)/',
					'strip' => 'das alles',
				),
				array(
					'regex' => '/.*(ist so ein)/',
					'strip' => 'ist so ein',
				),
			);
			/*
			 * try alternative regexes
			 */
			if (empty($matches[0])) {
				foreach ($alternative_regexes as $regex_data) {
					preg_match($regex_data['regex'], $message, $matches);
					$matches[0] = isset($matches[0]) ? array(mb_str_replace($regex_data['strip'], '', $matches[0])) : null;
				}
			}
			// still nothing found, skip
			if (empty($matches[0]))
				continue;

			// get longest match which also contains a keyword
			$longest_length = 0;
			$longest_key = 0;
			foreach ($matches[0] as $key => $match) {
				$current_length = mb_strlen($match);
				if (
					$current_length > $longest_length &&
					(
						mb_strpos($match, 'auf') !== FALSE ||
						mb_strpos($match, 'mit') !== FALSE ||
						mb_strpos($match, 'und') !== FALSE ||
						mb_strpos($match, 'in') !== FALSE
					)
				) {
					$longest_length = $current_length;
					$longest_key = $key;
				}
			}

			$mittagsmenue = $matches[0][$longest_key];

			// strip unwanted words from the beginning
			$unwanted_words_beginning = array(
				'ein', 'eine', 'einer', 'mit',
			);
			// longer strings first
			usort($unwanted_words_beginning, function($a,$b) {
				return mb_strlen($b) - mb_strlen($a);
			});
			$keys = array_keys($unwanted_words_beginning);
			for ($i=0; $i<count($keys); $i++) {
				$word = $unwanted_words_beginning[$i];
				if (mb_stripos($mittagsmenue, $word) === 0) {
					$mittagsmenue = mb_substr($mittagsmenue, mb_strlen($word));
					$mittagsmenue = trim($mittagsmenue);
					$i=-1; // restart
				}
			}

			// strip unwanted phrases
			$unwanted_phrases = array(
				'gibt es heute für euch', 'Sonne und Schanigarten',
			);
			foreach ($unwanted_phrases as $phrase)
				$mittagsmenue = mb_str_replace($phrase, '', $mittagsmenue);

			// clean menu
			$mittagsmenue = trim($mittagsmenue, "\n!;-:,.) ");

			// first character should be uppercase
			$mittagsmenue = ucfirst($mittagsmenue);			

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
