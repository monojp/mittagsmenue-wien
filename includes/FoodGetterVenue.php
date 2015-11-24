<?php

require_once(__DIR__ . '/includes.php');
require_once(__DIR__ . '/CacheHandler_MySql.php');

abstract class VenueStateSpecial {
	const Urlaub = 100;
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
	protected $no_menu_days = []; // 0 sunday - 6 saturday
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

		$this->CSSid = 'id_' . md5($this->dataSource . $this->title);
	}

	// overwrite in inherited class
	// provides an array of the possible today strings
	// which are used to perform searches on the menu data
	abstract protected function get_today_variants();

	// overwrite in inherited class
	// should parse datasource, set data, date, price, cache it and return it
	abstract protected function parseDataSource();

	// checks if the data is from today
	// used for caching
	protected function isDataUpToDate() {
		foreach ($this->get_today_variants() as $today) {
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

	private function isStateSpecial() {
		return in_array(
			$this->data, [
				VenueStateSpecial::Urlaub,
			]);
	}

	private function get_ajax_venue_code($date_GET, $cache = true) {
		$cache = $cache ? 'true' : 'false';
		return '
			head.ready([ "jquery" ], function() {
				$.ajax({
					type: "GET",
					url:  "/venue.php",
					cache: ' . $cache . ',
					data: {
						"classname": "' . get_class($this) . '",
						"timestamp": "' . $this->timestamp . '",
						"dateOffset": "' . $this->dateOffset . '",
						"date": "'. $date_GET . '"
					},
					success: function(result) {
						$("#' . $this->CSSid . '_data").html(result);
						head.ready([ "basic", "emojione" ], function() {
							emoji_update();
						});
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
			});
		';
	}

	public function parse_check() {
		// data valid and ok, do nothing
		if (
			$this->data &&
			($this->isDataUpToDate() || $this->isStateSpecial())
		)
			return $this->data;

		// another year, also do nothing. nevery parse another year!
		if (date('Y') != date('Y', $this->timestamp))
			return $this->data;

		$currentWeekYear = date('YW');
		$wantedWeekYear  = date('YW', $this->timestamp);

		// old data in general, we don't want that
		if ($wantedWeekYear < $currentWeekYear)
			return $this->data;

		// not lookaheadsafe and different week, do nothing
		if (
			!$this->lookaheadSafe &&
			$currentWeekYear != $wantedWeekYear
		)
			return $this->data;

		return $this->parseDataSource();
	}

	// main methode which will be called
	// queries the datasource for the menu
	// if a filterword is detected the line will be marked
	// caches the results
	public function getMenuData() {
		global $cacheDataExplode;
		global $explodeNewLines;
		global $date_GET;

		// query cache
		$this->cacheRead();

		// not valid or old data
		$data = $this->parse_check();

		// save special data to cache
		if ($this->isStateSpecial() && !$this->dataFromCache)
			$this->cacheWrite();

		// special state urlaub
		if ($data == VenueStateSpecial::Urlaub)
			return '<span class="error">Geschlossen wegen Urlaub/Feiertag</span><br />';

		//$data = create_ingredient_hrefs($data, $this->statisticsKeyword, 'menuData', false);

		// normalize unicode
		if (!Normalizer::isNormalized($data))
			$data = Normalizer::normalize($data);

		// replace html breaks with newlines
		$data = str_replace($explodeNewLines, "\n", $data);

		// convert special chars
		$data = htmlspecialchars($data, ENT_HTML5 | ENT_DISALLOWED);

		// replace newlines with html breaks
		$data = str_replace($explodeNewLines, '<br>', $data);

		// prepare return
		$return = isset($_GET['minimal']) ? '<br>' : '';

		if (
			!empty($this->data) &&
			$this->isDataUpToDate()
		) {
			// not from cache? => write back
			if (!$this->dataFromCache)
				$this->cacheWrite();

			$return .= "<div class='menu '>
					<a class='menuData dataSource color_inherit' href='{$this->dataSource}' target='_blank' title='Datenquelle' style='color: inherit ! important;'>Angebot:</a>
					<span> </span>
					<span class='menuData convert-emoji'>{$data}</span>
				</div>";

			if ($this->price && strpos($this->data, '€') === FALSE) {
				if (!is_array($this->price)) {
					$this->price = array($this->price);
				}
				foreach ($this->price as &$price) {
					if (is_numeric($price)) {
						$price = cleanText($price);
						$price = str_replace(',', '.', $price);
						$price = money_format('%.2n', $price);
					}
					else if (is_array($price)) {
						foreach ($price as &$p) {
							$p = trim($p, "/., \t\n\r\0\x0B");
							$p = str_replace(',', '.', $p);
							if (!empty($p))
								$p = money_format('%.2n', $p);
						}
						// remove empty values
						$price = array_filter($price);
						if (count($price) > 1) {
							$price_imploded = implode(' / ', $price);
							$price = "<a class='color_inherit' title='{$this->price_nested_info}' onclick='alert(\"{$this->price_nested_info}: {$price_imploded}\")' style='color: inherit ! important;'>({$price_imploded})<span class='raised'>i</span></a>";
						} else if (!empty($price)) {
							$price = '<span>' . reset($price) . '</span>';
						}
					}
				}
				// remove empty values
				$price = implode(' / ', array_filter($this->price));
				$return .= "Details: <b>{$price}</b> (<a href='{$this->menu}' class='color_inherit' target='_blank' style='color: inherit ! important;'>Speisekarte</a>)";
			}
		}
		else {
			// do randomized ajax reloads (60 - 120 seconds) of menu data
			$reload_code = '
				<script type="text/javascript">
					function reload_' . $this->CSSid . '() {
						var id = "' . $this->CSSid . '_data";
						$("#" + id).html(\'<div class="throbber middle">Lade...</div>\');
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
			$string .= "<a href='https://maps.google.com/maps?dirflg=r&amp;saddr=@@lat_lng@@&amp;daddr={$this->addressLat},{$this->addressLng}' class='no_decoration lat_lng_link' target='_blank'>
				<div data-enhanced='true' class='ui-link ui-btn ui-icon-location ui-btn-icon-notext ui-btn-inline ui-shadow ui-corner-all' title='Google Maps Route'>Google Maps Route</div>
			</a>";
		}
		// vote icon
		if (!isset($_GET['minimal']) && show_voting()) {
			$string .= "<div data-enhanced='true' class='ui-link ui-btn ui-icon-plus ui-btn-icon-notext ui-btn-inline ui-shadow ui-corner-all' onclick='vote_up(\"" . get_class($this) . "\")' title='Vote Up'>Vote Up</div>";
			$string .= "<div data-enhanced='true' class='ui-link ui-btn ui-icon-minus ui-btn-icon-notext ui-btn-inline ui-shadow ui-corner-all' onclick='vote_down(\"" . get_class($this) . "\")' data-role='button' data-inline='true' data-icon='minus' data-iconpos='notext' title='Vote Down'>Vote Down</div>";
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
					<div id="' . $this->CSSid . '_data">
						<script type="text/javascript">
							head.ready([ "jquery", "basic" ], function() {
								' . $this->get_ajax_venue_code($date_GET) . '
							});
						</script>
						<div class="throbber middle">Lade...</div>
					</div>
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
		$prices = [];
		foreach ($regexes as $regex) {
			preg_match_all($regex, $data, $priceTmp);
			if (empty($priceTmp))
				continue;
			foreach ($priceTmp[0] as $price) {
				preg_match($regex_price_cleaner, $price, $price);
				if (!empty($price[0]))
					$prices[] = $price[0];
			}
		}
		// cleanup
		$prices = array_map(function (&$val) { return str_replace(',', '.', trim($val, ' ,.&€')); }, $prices);
		// remove empty values
		$prices = array_filter($prices);
		// return
		return $prices;
	}

	/*
	 * returns the numerical amount of possible starters found in a string
	 */
	protected function get_starter_count($string) {
		$string = mb_strtolower($string);
		$string = str_replace([ "'", '"', '`', '´' ], '', $string);
		return (
			mb_substr_count($string, 'suppe') +
			mb_substr_count($string, 'bouillon') +
			mb_substr_count($string, 'minestrone') +
			mb_substr_count($string, 'salatteller') +
			mb_substr_count($string, 'vorspeise') +
			mb_substr_count($string, 'rindfleischsalat') +
			mb_substr_count($string, 'rohkostteller') +
			mb_substr_count($string, 'caesars salad') +
			mb_substr_count($string, 'brokkolisalat') +
			mb_substr_count($string, 'mozzarella mit paradeiser') +
			mb_substr_count($string, 'frischkäse-tartar') +
			mb_substr_count($string, 'rindfleisch auf blattsalat') +
			mb_substr_count($string, 'selleriesalat') +
			mb_substr_count($string, 'wurstsalat') +
			//mb_substr_count($string, 'salat mit') + // probleme mit zb "Schafskäse auf Blattsalat"
			mb_substr_count($string, 'rohkostsalat')
		);
	}

	/*
	 * returns the numerical amount of possible holiday strings found in a string
	 */
	protected function get_holiday_count($string) {
		$string = mb_strtolower($string);
		$string = str_replace([ "'", '"', '`', '´' ], '', $string);
		return (
			mb_substr_count($string, 'feiertag') +
			mb_substr_count($string, 'f e i e r t a g') +
			mb_substr_count($string, 'urlaub') +
			//mb_substr_count($string, 'geöffne') + // for e.g. Waldviertlerhof "Wir haben am 1. Mai geöffnet!" which is parsed as "ai geöffne"
			mb_substr_count($string, 'weihnacht') +
			mb_substr_count($string, 'oster') +
			mb_substr_count($string, 'himmelfahrt') +
			mb_substr_count($string, 'geschlossen') +
			mb_substr_count($string, 'pfingsten') +
			mb_substr_count($string, 'pfingstmontag') +
			mb_substr_count($string, 'fronleichnam')
		);
	}

	protected function get_dessert_count($string) {
		$string = mb_strtolower($string);
		$string = str_replace([ "'", '"', '`', '´' ], '', $string);

		// re-check if it's not a main food
		if (
			mb_strpos($string, 'tortelloni') !== false ||
			mb_strpos($string, 'letscho') !== false ||
			mb_strpos($string, 'nudeln') !== false
		) {
			return 0;
		}

		return (
			mb_substr_count($string, 'dessert') +
			mb_substr_count($string, 'yoghurt') +
			//mb_substr_count($string, 'joghurt') + // could also be a dressing
			//mb_substr_count($string, 'jogurt') +
			mb_substr_count($string, 'mousse') +
			mb_substr_count($string, 'kuchen') +
			mb_substr_count($string, 'torte') +
			mb_substr_count($string, 'schlag') +
			mb_substr_count($string, 'schoko') +
			mb_substr_count($string, 'muffin') +
			mb_substr_count($string, 'donut') +
			mb_substr_count($string, 'biskuit') +
			mb_substr_count($string, 'kompott') +
			mb_substr_count($string, 'palatschinke') +
			mb_substr_count($string, 'vanille') +
			mb_substr_count($string, 'topfencreme') +
			mb_substr_count($string, 'parfait') +
			mb_substr_count($string, 'eisbecher') +
			mb_substr_count($string, 'bananenschnitte') +
			mb_substr_count($string, 'topfenstrudel')
		);
	}

	protected function parse_foods_inbetween_days($data, $string_next_day, $string_last_day_next = [], $newline_replacer = "\n") {
		$today_variants = $this->get_today_variants();
		//return error_log(print_r($today_variants, true));

		// convert last_day_next to an array
		if (!is_array($string_last_day_next)) {
			$string_last_day_next = [ $string_last_day_next ];
		}
		//return error_log(print_r($string_last_day_next, true));

		// replace some strings that may interfer with day parsing
		$data = str_replace([ 'Montag-Samstag', 'Montag-Freitag' ], '', $data);

		foreach ($today_variants as $today) {
			$posStart = strposAfter($data, $today);
			// set date and stop if match found
			if ($posStart !== false) {
				//error_log("'${today}' found on pos ${posStart}");
				$this->date = $today;
				break;
			}
		}
		if ($posStart === false) {
			return;
		}
		$posEnd = mb_stripos($data, $string_next_day, $posStart);
		//error_log("'${string_next_day}' search returned ${posEnd}");
		// last day of the week (string)
		if ($posEnd === false && !is_array($string_last_day_next)) {
			$posEnd = mb_stripos($data, $string_last_day_next, $posStart);
			//error_log("'${string_last_day_next}' search returned ${posEnd}");
		// last day of the week (array of strings)
		} else if ($posEnd === false) {
			foreach ($string_last_day_next as $last_day_next) {
				$posEnd = mb_stripos($data, $last_day_next, $posStart);
				if ($posEnd !== false) {
					//error_log("'${last_day_next}' found on pos ${posEnd}");
					break;
				}
			}
		}
		if ($posEnd === false) {
			//error_log('no end found');
			return;
		 }
		//return error_log($posEnd);

		$data = mb_substr($data, $posStart, $posEnd - $posStart);
		//return error_log($data) && false;

		// check if holiday
		if ($this->get_holiday_count($data)) {
			return VenueStateSpecial::Urlaub;
		}

		// remove multiple newlines
		$data = preg_replace("/(\n)+/i", "\n", $data);
		$data = trim($data);

		// add seperator for 2nd line
		// this also prevents some of the following menu autonumber magic
		$data = preg_replace("/[\r\n]{1,}/", $newline_replacer, $data);

		//return error_log($data) && false;

		// menu magic via common parser helper (auto numbering and stuff)
		return $this->parse_foods_helper($data, $newline_replacer);
	}

	private function parse_foods_helper($dataTmp, $newline_replacer, &$prices = [],
			$end_on_friday = true, $one_price_per_food = true, $use_weekday_feature = false,
			$check_holiday_counts = true
	) {
		$data = null;
		$foodCount = 0; // 0 is monday, 6 is sunday
		$weekday = date('w', $this->timestamp) - 1;
		$weekday = ($weekday == -1) ? 6 : $weekday; // sunday fix
		$foodDone = false;
		$foodsMainCount = 0;

		$number_markers = [
			'IV.', 'III.', 'II.', 'I.',
			'1,', '2,', '3,', '4,',
			'1.', '2.', '3.', '4.',
			'1)', '2)', '3)', '4)',
			'1 ', '2 ', '3 ', '4 ', // macht evtl probleme mit dingen wie "1/2 Brathuhn"
		];

		$foods_title = [
			'Chicken Palak', 'Beef Shahi', 'Chana Masala', 'Beef Dusheri', 'Chicken Madras',
			'Aloo Palak', 'Chicken Masala', 'Beef Bhuna', 'Tarka Dal', 'Chicken Vindaloo',
			'Fish Madras', 'Mixed Sabji', 'Beef Chana', 'Navratan Korma', 'Chicken Ananas',
			'Butter Chicken', 'Chicken Makhani', 'Fish Masala', 'Zucchini Curry',
			'Chicken Tikka Masala', 'Chicken Sabji', 'Turkey Madras', 'Aloo Gobi Matar',
			'Chicken Malai', 'Beef Vindaloo', 'Beef Mango', 'Fish Bhuna', 'Chicken Dusheri',
			'Beef Madras', 'Aloo Gobi', 'Beef Kashmiri', 'Palak Paneer', 'Chicken Bhuna',
			'Fish Goa', 'Chicken Korma', 'Dal Makhani',
		];

		$regex_price = '/[0-9,\. ]*(€|EUR|Euro|euro|Tagesteller|Fischmenü|preis|Preis)+[0-9,\. ]*/';

		// remove multiple newlines
		$dataTmp = preg_replace("/(\n)+/i", "\n", $dataTmp);
		$dataTmp = trim($dataTmp);
		//return error_log($dataTmp);
		// split per new line
		$foods = explode_by_array([ "\n", "\r" ], $dataTmp);
		//return error_log(print_r($foods, true)) && false;

		// set date
		$this->date = reset($this->get_today_variants());

		// immediately return if we only have 1 element
		/*if (count($foods) == 1)
			return $dataTmp;*/

		foreach ($foods as $food) {
			$food = cleanText($food);
			//error_log($food);

			if (empty($food)) {
				continue;
			}

			// price entry
			if (
				(!$one_price_per_food || ($foodCount == ($weekday+1) || !$use_weekday_feature)) &&
				!empty($data) &&
				preg_match_all($regex_price, $food, $prices_temp)
			) {
				//error_log("found price in ${food}");
				$prices_temp = isset($prices_temp[0]) ? reset($prices_temp[0]) : null;
				preg_match_all('/\d*(,|\.)\d*/', $prices_temp, $prices_temp);
				$prices_temp = isset($prices_temp[0]) ? $prices_temp[0] : [];
				foreach ($prices_temp as $price) {
					$prices[] = str_replace(',', '.', trim($price, ' ,.&€'));
				}
			}

			// keywords indicating free day, increase foodCount day
			if ($use_weekday_feature && $check_holiday_counts
					&& $this->get_holiday_count($food) != 0
			) {
				// current day is free day
				if ($foodCount == $weekday) {
					return VenueStateSpecial::Urlaub;
				}
				$foodCount++;
			// nothing/too less found or keywords indicating noise
			} else if (
				!stringsExist($food, $number_markers) &&
				(mb_strlen($food) <= 5 ||
					mb_strlen(count_chars($food, 3)) <= 5 ||
					stringsExist($food, [
						'cafe', 'espresso', 'macchiato', 'capuccino', 'gondola', 'montag',
						'dienstag', 'mittwoch', 'donnerstag', 'freitag', 'samstag', 'sonntag',
						'gilt in', 'uhr', 'schanigarten', 'bieten', 'fangfrisch', 'ambiente',
						'reichhaltig', 'telefonnummer', 'willkommen', 'freundlich', 'donnerstag',
						'kleistgasse', 'tcpdf', 'take away', 'über die gasse', 'konsumation',
						'nur im haus', 'sehr geehrte', 'alle jahre wieder', 'location', 'feier',
						'unterstützen', 'angebote', 'grüße', 'team', 'gerne', 'termin', 'details',
						'anspruch', 'nummer',
					], true))
			) {
				//error_log("skip ${food}");
				continue;
			// keywords indicating end
			} else if (
				!$foodDone &&
				stringsExist($food, [ 'Allergen', 'Tisch reservieren', 'Menü:' ]) || ($end_on_friday && stringsExist($food, [ 'Freitag' ]))
			) {
				//error_log("done on ${food}");
				$foodDone = true;
			// first part of menu (soup)
			} else if (
				!$foodDone &&
				//empty($data) && // does unfortunately not work with the weekday independantly
				$this->get_starter_count($food) != 0
			) {
				if ($foodCount == $weekday || !$use_weekday_feature) {
					$data = $food;
				}
				$foodCount++;
			// second part of menu
			} else if (
				!$foodDone &&
				($foodCount == ($weekday+1) || !$use_weekday_feature) /*&&
				!empty($data)*/ // don't use this, otherwise we need a soup
			) {
				// do newline replacers when the output string is not empty
				// AND whole menu contains a number marker and new food doesn't (avoids problems with menus stretching over multiple lines)
				if (
					!empty($data) &&
					(!stringsExist($data, $number_markers) || stringsExist($food, $number_markers))
				) {
					// we are continuing the old food here
					if (
						mb_stripos($food, 'mit')  === 0 || endswith($data, 'mit')  ||
						mb_stripos($food, 'dazu') === 0 || endswith($data, 'dazu') ||
						mb_stripos($food, 'und')  === 0 || endswith($data, 'und') ||
						mb_stripos($food, 'auf')  === 0 || endswith($data, 'auf')
					) {
						$data .= ' ';
					// we have a food part and title that is already written
					} else if (
						stringsExist(end(explode_by_array($newline_replacer, $data)), $foods_title)
						&& !stringsExist($food, $foods_title)
					) {
						$data .= ': ';
					// use default newline replacer
					} else {
						$data .= $newline_replacer;
					}
				}
				$data .= $food;
			}
			//error_log($food);
		}
		//return error_log($data) && false;

		// replace common noise strings
		$data = str_replace($number_markers, '', $data);

		$foods = explode_by_array([ "\n", "\r" ], $data);
		//return error_log(print_r($foods, true)) && false;

		// strip price infos from foods
		foreach ($foods as &$food) {
			$food = preg_replace($regex_price, ' ', $food);
			$food = cleanText($food);
		}
		unset($food);

		// remove empty values
		$foods = array_filter($foods);
		//return error_log(print_r($foods, true)) && false;

		// count main foods
		foreach ($foods as $food) {
			if (
				$this->get_starter_count($food) == 0 &&
				$this->get_dessert_count($food) == 0
			) {
				//error_log($food);
				$foodsMainCount++;
			}
			/*else {
				error_log($food);
			}*/
		}

		// add counts (as long food is no dessert or soup)
		if ($foodsMainCount > 1) {
			$foodCount = 1;
			foreach ($foods as &$food) {
				if (
					$this->get_dessert_count($food) == 0 &&
					$this->get_starter_count($food) == 0
				) {
					$food = $foodCount . '. ' . trim($food);
					$foodCount++;
					//error_log($food);
				}
			}
			unset($food);
		}

		$data = implode("\n", $foods);
		//return error_log($data) && false;

		// check if holiday
		if ($check_holiday_counts && $this->get_holiday_count($data)) {
			return VenueStateSpecial::Urlaub;
		}

		return cleanText($data);
	}

	protected function parse_foods_independant_from_days($dataTmp, $newline_replacer,
			&$prices = null, $end_on_friday = true, $one_price_per_food = true,
			$check_holiday_counts = true
	) {
		return $this->parse_foods_helper($dataTmp, $newline_replacer, $prices, $end_on_friday,
				$one_price_per_food, true, $check_holiday_counts);
	}

	protected function mensa_menu_get($dataTmp, $title_search, $timestamp, $need_starter, &$price_return=null) {
		$title_pos = strrpos($dataTmp, $title_search);
		if ($title_pos === false) {
			return null;
		}
		$posStart = strnposAfter($dataTmp, 'menu-item-text">', $title_pos, date('N', $timestamp));
		if ($posStart === false) {
			return null;
		}

		$posEnd = mb_stripos($dataTmp, '</div>', $posStart);
		$data = mb_substr($dataTmp, $posStart, $posEnd - $posStart);
		$data = strip_tags($data, '<br>');
		//return error_log($data);

		// remove multiple newlines
		$data = preg_replace("/(\n)+/i", "\n", $data);
		$data = trim($data);
		// split per new line
		$foods = explode("\n", $data);
		//return print_r(error_log($data), true);

		$data = null;
		$cnt = 1;
		foreach ($foods as $food) {
			$food = cleanText($food);
			if (!empty($food)) {
				if ($cnt == 1) {
					$data .= $food;
				} else if (
					($cnt == 2 && mb_stripos($data, 'suppe') !== false) || // suppe, xx
					$cnt == count($foods) // xx, dessert
				) {
					$data .= ", $food";
				} else if (strpos($food, '€') !== false) {
					if ($price_return !== null) {
						$price_return = $food;
					}
				} else {
					$data .= " $food";
				}
				$cnt++;
			}
		}

		if ($need_starter && $this->get_starter_count($data) === 0) {
			$data = $price_return = null;
		}

		return empty($data) ? '-' : $data;
	}
}
