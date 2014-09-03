<?php

require_once(__DIR__ . '/../FB_Helper.php');
require_once(__DIR__ . '/../includes.php');

class Woracziczky extends FoodGetterVenue {

	function __construct() {
		$this->title = 'Gasthaus Woracziczky';
		//$this->title_notifier = 'NEU';
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

		foreach ((array)$all_posts as $post) {
			if (!isset($post['message']) || !isset($post['created_time']))
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
			$words_relevant = array(
				'Mittagspause', 'Mittagsmenü', 'was gibts', 'bringt euch', 'haben wir', 'gibt\'s', 'Essen', 'Mahlzeit',
				'Vorspeise', 'Hauptspeise', 'bieten euch', 'bis gleich', 'gibt', 'haben heute', 'servieren euch', 'Bis gleich',
				'schmecken', 'freuen uns auf euch', 'neue Woche', 'wartet schon auf euch', 'freuen sich heute auf euch',
				'freuen sich auf euch', 'erwarten euch',
			);
			if (!stringsExist($message, $words_relevant))
				continue;

			//error_log($message);

			/* try form
			 * <random text>
			 * <lunch info>
			 * <random text>
			 */

			$regexes = array(
				array(
					'regex' => '/(heute im Wora gibt\?\n).[^\r\n]*/',
					'strip' => 'heute im Wora gibt?',
				),
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
				array(
					'regex' => '/.*(gibt es heute)/',
					'strip' => 'gibt es heute',
				),
				array(
					'regex' => '/.*(bei uns genießen)/',
					'strip' => 'bei uns genießen',
				),
				array(
					'regex' => '/.*(bekommt ihr heute von uns serviert)/',
					'strip' => 'bekommt ihr heute von uns serviert',
				),
				array(
					'regex' => '/.*(sollen euch heute die nötige Energie für)/',
					'strip' => 'sollen euch heute die nötige Energie für'
				),
				array(
					'regex' => '/.*(erwarten euch heute)/',
					'strip' => 'erwarten euch heute',
				),
				array(
					'regex' => '/.*[\r\n]*(stärken euch heute in der Mittagspause)/',
					'strip' => 'stärken euch heute in der Mittagspause',
				),
				array(
					'regex' => '/(haben wir heute).*(für euch)/',
					'strip' => array('haben wir heute', 'für euch'),
				),
				array(
					'regex' => '/(heute für euch im Schanigarten).*/',
					'strip' => 'heute für euch im Schanigarten',
				),
				array(
					'regex' => '/(trotzen den Wolken).*/',
					'strip' => 'trotzen den Wolken',
				),
				array(
					'regex' => '/(gibts).*/',
					'strip' => 'gibts',
				),
				array(
					'regex' => '/(unser heutiges Mittagsmenü:\n).*(\.)/',
					'strip' => 'unser heutiges Mittagsmenü:',
				),
				array(
					'regex' => '/zu Mittag!\n.*/',
					'strip' => 'zu Mittag',
				),
				array(
					'regex' => '/.*(als Hauptspeise)/',
					'strip' => 'als Hauptspeise',
				),
				array(
					'regex' => '/.*\n\n(freuen sich schon auf eure Mittagspause)/',
					'strip' => 'freuen sich schon auf eure Mittagspause',
				),
				array(
					'regex' => '/\n\n.+\n\n/', // most common
				),
				array(
					'regex' => '/\n\n.*\n.*\n\n/',
				),
			);
			/*
			 * try regexes
			 */
			foreach ($regexes as $regex_data) {
				preg_match($regex_data['regex'], $message, $matches);

				if (empty($matches))
					continue;

				//error_log(print_r($matches, true));

				// fix to get same outcome like preg_match_all
				if (!isset($regex_data['strip'] )) {
					$matches[0] = array($matches[0]);
					//continue;
					break;
				}

				// strip array of strings
				if (is_array($regex_data['strip'])) {
					//error_log($matches[0]);
					$matches[0] = isset($matches[0]) ? $matches[0] : null;
					foreach ((array)$regex_data['strip'] as $strip_data) {
						$matches[0] = mb_str_replace($strip_data, '', $matches[0]);
					}

					// nothing found or found stuff too short, skip
					if (strlen($matches[0]) < 15)
						continue;

					$matches[0] = array($matches[0]);

					break;
				}
				// string single string
				else {
					//error_log($matches[0]);
					$matches[0] = isset($matches[0]) ? mb_str_replace($regex_data['strip'], '', $matches[0]) : null;
					//error_log($matches[0]);

					// nothing found or found stuff too short, skip
					if (strlen($matches[0]) < 15)
						continue;

					$matches[0] = array($matches[0]);

					break;
				}
			}
			//error_log(print_r($matches, true));
			//break;

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

			// nothing found or found stuff too short, skip
			if ($longest_length < 15)
				continue;

			$mittagsmenue = trim($matches[0][$longest_key]);
			//error_log($mittagsmenue);
			//break;

			// strip unwanted phrases
			$unwanted_phrases = array(
				'gibt es heute für euch', 'Sonne und Schanigarten', 'als Hauptgericht', 'Heute gibt\'s bei uns', 'Wora', 'Essen',
				'als Vorspeise', 'als Hauptspeise', 'Sonne pur', 'Schanigarten', 'bieten euch heute', 'Sonnenschein', 'strahlender',
				'gemütlichen', 'als wärmende Vorspeise', 'als köstliche Hauptspeise', 'als feine Hauptspeis', 'warten auf euch',
				'als Hauptgang', 'freuen sich auf euch', 'freuen sich heute auf euch', 'erwarten euch', 'bis gleich',
				'lasst es euch schmecken', 'Mahlzeit', 'Guten Appetit', 'zur Vorspeise', 'zur Hauptspeise',
			);
			foreach ($unwanted_phrases as $phrase)
				$mittagsmenue = mb_str_replace($phrase, '', $mittagsmenue);

			$mittagsmenue = trim($mittagsmenue, "\n!;-:,.-() ");

			//error_log("'$mittagsmenue'");
			//break;

			// strip unwanted words from the beginning or end
			$unwanted_words_beginning = array(
				'ein', 'eine', 'einer', 'einen', 'mit', 'im', 'wir', 'und', 'ihr', 'könnt', 'heute',
			);
			// longer strings first
			usort($unwanted_words_beginning, function($a,$b) {
				return mb_strlen($b) - mb_strlen($a);
			});
			$keys = array_keys($unwanted_words_beginning);
			for ($i=0; $i<count($keys); $i++) {
				$word = $unwanted_words_beginning[$i];
				if (mb_stripos($mittagsmenue, $word) === 0) {
					//error_log("stripped $word from beginning");
					$mittagsmenue = mb_substr($mittagsmenue, mb_strlen($word));
					$mittagsmenue = trim($mittagsmenue, "\n!;-:,.-() ");
					$i=-1; // restart
				}
				else if (mb_stripos($mittagsmenue, $word) === strlen($mittagsmenue) - strlen($word) - 1) {
					//error_log("stripped $word from end");
					$mittagsmenue = mb_substr($mittagsmenue, 0, strlen($mittagsmenue) - strlen($word) - 1);
					$mittagsmenue = trim($mittagsmenue, "\n!;-:,.-() ");
					$i=-1; // restart
				}
			}

			//error_log("'$mittagsmenue'");
			//break;

			// replace multiple whitespaces with one
			$mittagsmenue = preg_replace('/\s{2,}/', ' ', $mittagsmenue);

			// clean menu
			$mittagsmenue = trim($mittagsmenue, "\n!;-:,.-() ");

			// first character should be uppercase
			$mittagsmenue = ucfirst($mittagsmenue);

			//error_log($mittagsmenue);
			//break;

			$data = $mittagsmenue;
			$today = getGermanDayName();
		}

		// all posts scanned, nothing found, but relevant string in a posts => maybe vacation!
		if (empty($data) && mb_stripos(print_r($all_posts, true), 'urlaub') !== false)
			$data = VenueStateSpecial::UrlaubMaybe;

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