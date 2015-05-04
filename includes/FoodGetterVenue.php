<?php

require_once(__DIR__ . '/includes.php');
require_once(__DIR__ . '/CacheHandler_MySql.php');

define('DIRECT_SHOW_MAX_LENGTH', 256);

abstract class VenueStateSpecial {
	const Urlaub      = 100;
	const UrlaubMaybe = 101;
}

/*
 * Venue Class
 */
abstract class FoodGetterVenue {
	public $title = null;
	public $url = null;
	protected $title_notifier = null; // presented small highlighted next to the title
	protected $addressLat = null;
	protected $addressLng = null;
	protected $dataSource = null;
	protected $menu = null;
	protected $data = null;
	protected $date = null;
	protected $price = null;
	protected $statisticsKeyword = null;
	protected $no_menu_days = array(); // 0 sunday - 6 saturday
	protected $lookaheadSafe = false; // future lookups possible? otherwise only current week (e.g. because dayname check)
	protected $dataFromCache = false;
	protected $price_nested_info = null;
	private $CSSid = null;

	// constructor
	// set date offset via get parameter
	function __construct() {
		global $dateOffset, $timestamp;

		$this->dateOffset = $dateOffset;
		$this->timestamp = $timestamp;

		// fix wrong lat/lng values
		$this->addressLat = str_replace(',', '.', $this->addressLat);
		$this->addressLng = str_replace(',', '.', $this->addressLng);

		$this->CSSid = 'id_' . md5($this->dataSource);
	}

	// overwrite in inherited class
	// provides an array of the possible today strings
	// which are used to perform searches on the menu data
	abstract protected function get_today_variants();

	// overwrite in inherited class
	// should parse datasource, set data, date, price, cache it and return it
	abstract protected function parseDataSource();

	// overwrite in inherited class
	// should check if the data is from today
	// used for caching
	//abstract public function isDataUpToDate();

	protected function isDataUpToDate() {
		//return false;
		$today_variants = $this->get_today_variants();

		foreach ($today_variants as $today) {
			if ($this->date == $today)
				return true;
		}
		return false;
	}

	// writes data to the cache
	protected function cacheWrite() {
		CacheHandler_MySql::getInstance($this->timestamp)->saveToCache($this->dataSource, $this->date, $this->price, $this->data);
	}
	// reads data from the cache
	protected function cacheRead() {
		$this->dataFromCache = CacheHandler_MySql::getInstance($this->timestamp)->getFromCache($this->dataSource, $this->date, $this->price, $this->data);
	}

	private function get_ajax_venue_code($date_GET, $cache = true) {
		$cache = $cache ? 'true' : 'false';
		return '
			$.ajax({
				type: "GET",
				url:  "venue.php",
				cache: ' . $cache . ',
				data: {
					"classname": "' . get_class($this) . '",
					"timestamp": "' . $this->timestamp . '",
					"dateOffset": "' . $this->dateOffset . '",
					"date": "'. $date_GET . '"
				},
				success: function(result) {
					$("#' . $this->CSSid . '_data").html(result);
				},
				error: function() {
					var errMsg = $(document.createElement("span"));
					errMsg.attr("class", "error");
					errMsg.html("Fehler beim Abfragen der Daten :(");
					errMsg.prepend($(document.createElement("br")));
					$("#' . $this->CSSid . '_data").empty();
					$("#' . $this->CSSid . '_data").append(errMsg);
				}
			});
		';
	}

	// main methode which will be called
	// queries the datasource for the menu
	// if a filterword is detected the line will be marked
	// caches the results
	public function getMenuData() {
		global $cacheDataExplode;
		global $cacheDataIgnore;
		global $explodeNewLines;
		global $date_GET;

		// query cache
		$this->cacheRead();

		// not valid or old data
		if (!$this->data || !$this->isDataUpToDate()) {
			// avoid querying other week than current
			// fixes cache problems
			$currentWeekYear = date('YW');
			$wantedWeekYear = date('YW', $this->timestamp);
			//error_log(date('W', $this->timestamp));
			if (
				($currentWeekYear == $wantedWeekYear) || // current week only
				($this->lookaheadSafe && $currentWeekYear < $wantedWeekYear) || // lookaheadsafe menus work with future weeks also
				($this->lookaheadSafe && $wantedWeekYear == 1) // because of ISO 8601 last week of year is sometimes returned as 1
			)
				$this->parseDataSource();
		}

		$data = $this->data;

		// special state urlaub
		if ($data == VenueStateSpecial::Urlaub)
			return '<br /><span class="error">Zurzeit geschlossen wegen Urlaub</span><br />';
		else if ($data == VenueStateSpecial::UrlaubMaybe)
			return '<br /><span class="error">Vermutlich zurzeit geschlossen wegen Urlaub</span><br />';

		//$data = create_ingredient_hrefs($data, $this->statisticsKeyword, 'menuData', false);

		// run filter
		if ($data) {
			$data = explode_by_array($explodeNewLines, $data);
			foreach ($data as &$dElement) {
				// remove 1., 2., stuff
				$foodClean = str_replace($cacheDataIgnore, '', $dElement);
				$foodClean = trim($foodClean);
				$foodClean = explode_by_array($cacheDataExplode, $foodClean);
			}
			$data = implode('<br />', $data);
		}

		// prepare return
		$return = '';

		// old data and nothing found => don't display anything
		$currentWeek = date('W');
		$wantedWeek = date('W', $this->timestamp);
		if ($currentWeek > $wantedWeek && !$this->data) // TODO DATE REPLACE
			$data = null;

		if (!empty($this->data) && $this->isDataUpToDate()) {
			// not from cache? => write back
			if (!$this->dataFromCache)
				$this->cacheWrite();

			// dirty encode & character
			// can't use htmlspecialchars here, because we need those ">" and "<"
			$data = str_replace("&", "&amp;", $data);

			$angebot_link = "<a class='menuData dataSource color_inherit' href='{$this->dataSource}' target='_blank' title='Datenquelle'>Angebot:</a>";
			$return .= "<div class='menu '>{$angebot_link} <span class='menuData '>{$data}</span></div>";

			if ($this->price && strpos($this->data, '€') === FALSE) {
				if (!is_array($this->price)) {
					$this->price = array($this->price);
				}
				foreach ($this->price as &$price) {
					if (!is_array($price)) {
						$price = cleanText($price);
						$price = str_replace(',', '.', $price);
						$price = money_format('%.2n', $price);
					}
					else {
						foreach ($price as &$p) {
							$p = trim($p, "/., \t\n\r\0\x0B");
							$p = str_replace(',', '.', $p);
							if (!empty($p))
								$p = money_format('%.2n', $p);
						}
						// remove empty values
						$price = array_filter($price);
						if (count($price) > 1)
							$price = "<span title='{$this->price_nested_info}'>(" . implode(' / ', $price) . ')<span class="raised">i</span></span>';
						else if (!empty($price))
							$price = '<span>' . reset($price) . '</span>';
					}
				}
				// remove empty values
				$price = implode(' / ', array_filter($this->price));
				$return .= "Details: <b>{$price}</b> (<a href='{$this->menu}' class='color_inherit' target='_blank'>Speisekarte</a>)";
			}
		}
		else {
			// do randomized ajax reloads (60 - 120 seconds) of menu data
			$reload_code = '
				<script type="text/javascript">
					function reload_' . $this->CSSid . '() {
						var id = "' . $this->CSSid . '_data";
						$("#" + id).html(\'<img src="imagesCommon/loader.gif" width="160" height="24" alt="" style="vertical-align: middle" />\');
						' . $this->get_ajax_venue_code($date_GET, false) . '
					}
					function set_rand_reload_' . $this->CSSid . '() {
						setTimeout(function() {
							reload_' . $this->CSSid . '();
						}, Math.floor((Math.random() * 60) + 60) * 1000);
					}
					set_rand_reload_' . $this->CSSid . '();
				</script>
				<a href="javascript:void(0)" onclick="reload_' . $this->CSSid . '()">aktualisieren</a>
			';
			$return .= '<br />';
			$return .= '<span class="error">Leider nichts gefunden :(</span>';
			$return .= '<br />';
			$return .= "<a href='{$this->dataSource}' target='_blank'>Mittagskarte</a> / <a href='{$this->menu}' target='_blank'>Speisekarte</a>";

			// not minimal => reload action
			if (!isset($_GET['minimal']))
				$return .= " / ${reload_code}";
		}

		return $return;
	}

	// gets menu data and prints it in a nice readable form
	public function __toString() {
		global $date_GET;

		$string = '';

		// if minimal (JS free) site requested => show venues immediately
		if (!isset($_GET['minimal']))
			$attributeStyle = 'display: none';
		else
			$attributeStyle = '';

		$string .= "<div id='{$this->CSSid}' class='venueDiv' style='{$attributeStyle}'>";
		// hidden lat/lng spans
		if (!isset($_GET['minimal'])) {
			$string .= "<span class='hidden lat'>{$this->addressLat}</span>";
			$string .= "<span class='hidden lng'>{$this->addressLng}</span>";
		}

		// title
		$string .= "<span class='title' title='Homepage'><a href='{$this->url}' class='no_decoration' target='_blank'>{$this->title}</a></span>";
		if ($this->title_notifier)
			$string .= "<span class='title_notifier'>{$this->title_notifier}</span>";
		// address icon with route planner
		if ($this->addressLat && $this->addressLng) {
			$string .= "<a class='lat_lng_link' href='https://maps.google.com/maps?dirflg=r&amp;saddr=@@lat_lng@@&amp;daddr=" . $this->addressLat . "," . $this->addressLng . "' target='_blank'><span class='icon sprite sprite-icon_pin_map' title='Google Maps Route'></span></a>";
		}
		// vote icon
		if (!isset($_GET['minimal']) && show_voting()) {
			$string .= "<a href='javascript:void(0)' onclick='vote_up(\"" . get_class($this) . "\")'><span class='icon sprite sprite-icon_hand_pro' title='Vote Up'></span></a>";
			$string .= "<a href='javascript:void(0)' onclick='vote_down(\"" . get_class($this) . "\")'><span class='icon sprite sprite-icon_hand_contra' title='Vote Down'></span></a>";
		}

		// check no menu days
		$no_menu_day = false;
		$dayNr = date('w', $this->timestamp);
		foreach ((array)$this->no_menu_days as $day) {
			if ($dayNr == $day) {
				$dayName = getGermanDayName();
				$string .= "<br /><span class='error'>Leider kein Mittagsmenü am {$dayName} :(</span><br />";
				$no_menu_day = true;
				break;
			}
		}

		if (!$no_menu_day) {
			// old data getter
			if (isset($_GET['minimal']))
				$string .= $this->getMenuData();
			// new data getter via ajax
			else
				$string .= '
					<span id="' . $this->CSSid . '_data">
						<script type="text/javascript">
							head.ready("scripts", function() {
								if (jQuery.inArray("' . get_class($this) . '", venues_ajax_query) == -1) {
									venues_ajax_query.push("' . get_class($this) . '");
									' . $this->get_ajax_venue_code($date_GET) . '
								}
							});
						</script>
						<br />
						<img src="imagesCommon/loader.gif" width="160" height="24" alt="" style="vertical-align: middle" />
					</span>
				';
		}
		$string .= '</div>';

		return $string;
	}

	// ------------------
	// DATE CHECK HELPERS
	// ------------------
	protected function in_date_range_string($string, $timestamp, $start_format = '%d. %B', $end_format = '%d. %B') {
		preg_match('/[\d]+\.(.)+(-|—|–|bis)(.)*[\d]+\.(.)+/', $string, $date_check);
		if (empty($date_check) || !isset($date_check[0]))
			return;
		$date_check = explode_by_array(array('-', '—', '–', 'bis'), $date_check[0]);
		$date_check = array_map('trim', $date_check);
		$date_start = strtotimep($date_check[0], $start_format, $timestamp);
		$date_end   = strtotimep($date_check[1], $end_format, $timestamp);
		//return (error_log(print_r($date_check, true)) && false && true);
		//return (error_log(date('d.m.Y', $date_start)) && false && true);
		//return (error_log(date('d.m.Y', $date_end)) && false && true);
		//return (error_log($date_start) && error_log($date_end) && error_log($timestamp) && false && true);
		//return (error_log($timestamp >= $date_start && $timestamp <= $date_end) && false && true);
		return ($timestamp >= $date_start && $timestamp <= $date_end);
	}

	// -------------
	// PARSE HELPERS
	// -------------
	protected function parse_prices_regex($data, $regexes, $regex_price_cleaner = '/\d,(\d){1,2}/') {
		$prices = array();
		foreach ($regexes as $regex) {
			preg_match($regex, $data, $priceTmp);
			preg_match($regex_price_cleaner, $priceTmp[0], $priceTmp);
			$prices[] = $priceTmp[0];
		}
		return $prices;
	}

	protected function parse_foods_inbetween_days($data, $string_next_day, $string_last_day_next) {
		$today_variants = $this->get_today_variants();
		//return error_log(print_r($today, true));

		foreach ($today_variants as $today) {
			$posStart = strposAfter($data, $today);
			// set date and stop if match found
			if ($posStart !== false) {
				$this->date = $today;
				break;
			}
		}
		if ($posStart === false)
			return;
		//return error_log($posStart);
		$posEnd = mb_stripos($data, $string_next_day, $posStart);
		// last day of the week
		if ($posEnd === false)
			$posEnd = mb_stripos($data, $string_last_day_next, $posStart);
		if ($posEnd === false)
			return;
		//return error_log($posEnd);
		return mb_substr($data, $posStart, $posEnd - $posStart);
	}

	/*
	 * returns the numerical amount of possible soup strings found in a string
	 */
	protected function get_soup_count($string) {
		$string = strtolower($string);
		return (
			substr_count($string, 'suppe') +
			substr_count($string, 'minestrone')
		);
	}

	/*
	 * returns the numerical amount of possible holiday strings found in a string
	 */
	protected function get_holiday_count($string) {
		$string = strtolower($string);
		return (
			substr_count($string, 'feiertag') +
			substr_count($string, 'urlaub') +
			substr_count($string, 'geöffne') + // for e.g. Waldviertlerhof "Wir haben am 1. Mai geöffnet!" which is parsed as "ai geöffne"
			substr_count($string, 'weihnacht') +
			substr_count($string, 'oster')
		);
	}

	protected function get_dessert_count($string) {
		$string = strtolower($string);
		return (
			substr_count($string, 'dessert') +
			substr_count($string, 'yoghurt') +
			substr_count($string, 'joghurt') +
			substr_count($string, 'jogurt') +
			substr_count($string, 'mousse') +
			substr_count($string, 'kuchen')
		);
	}

	protected function parse_foods_independant_from_days($foods, $newline_replacer, &$prices, $end_on_friday = true, $one_price_per_food = true) {
		$data = null;
		$mainCount = 0; // counts how many main foods there are
		$foodCount = 0; // 0 is monday, 6 is sunday
		$weekday = date('w', $this->timestamp) - 1;
		$weekday = ($weekday == -1) ? 6 : $weekday; // sunday fix
		$foodDone = false;

		$regex_price = '/(.*)(€|EUR|Euro|Tagesteller|Fischmenü)(.*)(,.)(.*)/';

		foreach ($foods as $food) {
			$food = cleanText($food);
			// keywords indicating free day, increase foodCount day
			if ($this->get_holiday_count($food) != 0)
				$foodCount++;
			// price entry
			else if (
				(!$one_price_per_food || ($foodCount == ($weekday+1))) &&
				!empty($data) &&
				preg_match_all($regex_price, $food, $prices_temp)
			) {
				//error_log("found price in ${food}");
				$prices_temp = isset($prices_temp[0]) ? reset($prices_temp[0]) : null;
				preg_match_all('/\d*(,|\.)\d*/', $prices_temp, $prices_temp);
				$prices_temp = isset($prices_temp[0]) ? $prices_temp[0] : array();
				foreach ($prices_temp as $price) {
					$prices[] = str_replace(',', '.', trim($price, ' ,.&€'));
				}
			}
			// nothing/too less found or keywords indicating noise#
			else if (
				strlen($food) < 10 ||
				strlen(count_chars($food, 3)) <= 5 ||
				stringsExist($food, array(
					'cafe', 'espresso', 'macchiato', 'capuccino', 'gondola', 'euro', '€', 'montag',
					'dienstag', 'mittwoch', 'donnerstag', 'freitag', 'gilt in', 'uhr', 'schanigarten',
					'bieten', 'fangfrisch', 'ambiente', 'reichhaltig', 'telefonnummer', 'willkommen',
					'freundlich', 'donnerstag', 'kleistgasse',
				), true)
			) {
				//error_log("skip ${food}");
				continue;
			}
			// keywords indicating end
			else if (
				!$foodDone &&
				stringsExist($food, array('Allergen',)) || ($end_on_friday && stringsExist($food, array('Freitag')))
			) {
				//error_log("done on ${food}");
				$foodDone = true;
			}
			// first part of menu (soup)
			else if (
				!$foodDone &&
				$this->get_soup_count($food) != 0
			) {
				if ($foodCount == $weekday)
					$data = $food;
				$foodCount++;
			}
			// second part of menu
			else if (
				!$foodDone &&
				$foodCount == ($weekday+1) &&
				!empty($data)
			) {
				$mainCount++;
				$data .= "${newline_replacer}${food}";
			}
			//error_log($food);
		}

		// replace common noise strings
		$data = str_replace(array('III.', 'II.', 'I.', '1.', '2.', '3.'), '', $data);

		// add counts (as long food is no dessert or soup)
		$foodCount = 1;
		$foods = explode_by_array(array("\n", "\r"), $data);
		foreach ($foods as &$food) {
			if (
				$this->get_dessert_count($food) == 0 &&
				$this->get_soup_count($food) == 0
			) {
				$food = $foodCount . '. ' . $food;
				$foodCount++;
			}
		}
		unset($food);
		$data = implode("\n", $foods);

		return $data;
	}
}
