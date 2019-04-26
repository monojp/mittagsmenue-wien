<?php

namespace App\Parser\Venue;

use App\Legacy\ContainerHelper;
use App\Parser\FacebookService;
use App\Parser\Strings;
use App\Parser\TextService;
use App\Parser\TimeService;
use App\Service\FormatService;
use App\Service\VenueService;
use RuntimeException;

abstract class VenueStateSpecial {
	const Urlaub = 100;
	const None = 200;
}

/*
 * Venue Class
 */
abstract class FoodGetterVenue {
	public $title;
	public $url;
	public $addressLat;
	public $addressLng;

	protected $title_notifier; // presented small highlighted next to the title
	protected $dataSource;
	protected $menu;
	protected $data;
	protected $price;
	protected $no_menu_days = []; // 0 sunday - 6 saturday
	protected $lookaheadSafe = false; // future lookups possible? otherwise only current week (e.g. because dayname check)
	protected $dataFromCache = false;
	protected $dataParsed = false;
	protected $price_nested_info;

	private $id;

	const SECONDS_EMPTY_CACHE = 300;

	// constructor
	// set date offset via get parameter
	function __construct() {
		global $dateOffset, $timestamp;

		$this->dateOffset = $dateOffset;
		$this->timestamp = $timestamp;

		// fix wrong lat/lng values
		$this->addressLat = str_replace(',', '.', $this->addressLat);
		$this->addressLng = str_replace(',', '.', $this->addressLng);

		$className = get_class($this);
        $className = explode('\\', $className);
        $this->id = array_pop($className);
	}

	// overwrite in inherited class
	// provides an array of the possible today strings
	// which are used to perform searches on the menu data
	abstract protected function get_today_variants();

	// overwrite in inherited class
	// should parse datasource, set data, date, price, cache it and return it
	abstract protected function parseDataSource();

	// writes data to the cache
	protected function cacheWrite() {
		if (!getenv('PERSIST_MENU_DATA')) {
			return;
		}

        $errors = ContainerHelper::getInstance()->get(VenueService::class)->saveToCache(
            $this->timestamp,
            $this->id,
            $this->data,
            $this->price
        );

		// FIXME handle errors in GUI
        if ($errors && count($errors)) {
            throw new RuntimeException(print_r($errors, true));
        }
	}
	// reads data from the cache
	protected function cacheRead() {
		if (!getenv('PERSIST_MENU_DATA')) {
			return;
		}

		$foodCache = ContainerHelper::getInstance()->get(VenueService::class)->getCache($this->timestamp, $this->id);
		if ($foodCache) {
            // reset too old empty cached data
            if ((int)$foodCache->getData() == VenueStateSpecial::None
                && (time() - $foodCache->getChanged()->getTimestamp()) > self::SECONDS_EMPTY_CACHE) {
                ContainerHelper::getInstance()->get(VenueService::class)->deleteFromCache($foodCache);
                return;
            }

            $this->dataFromCache = true;
            $this->data = $foodCache->getData();
            $this->price = json_decode($foodCache->getPrice(), true);
        } else {
            $this->dataFromCache = false;
        }
	}

	private function isStateSpecial() {
		return in_array($this->data, [ VenueStateSpecial::Urlaub, VenueStateSpecial::None ]);
	}

	private function get_ajax_venue_code($date_GET, $cache = true) {
		$cache = $cache ? 'true' : 'false';
		return '
			$.ajax({
				type: "GET",
				url:  "/venue/' . $this->id . '/menu",
				cache: ' . $cache . ',
				data: {
					"timestamp": "' . $this->timestamp . '"
				},
				success: function(result) {
					$("#' . $this->id . '_data").html(result);
				},
				error: function() {
					var errMsg = $(document.createElement("span"));
					errMsg.attr("class", "error");
					errMsg.html("Fehler beim Abfragen der Daten :(");
					errMsg.prepend($(document.createElement("br")));
					$("#' . $this->id . '_data").empty();
					$("#' . $this->id . '_data").append(errMsg);
				}
			});
		';
	}

	public function parse_check() {
		// data valid and ok, do nothing
		if ($this->data || $this->dataFromCache) {
			return $this->data;
		}

		// another year, also do nothing. never parse another year!
		if (date('Y') != date('Y', $this->timestamp)) {
			return $this->data;
		}

		$currentWeekYear = date('YW');
		$wantedWeekYear = date('YW', $this->timestamp);

		// old data in general, we don't want that
		if ($wantedWeekYear < $currentWeekYear) {
			return $this->data;
		}

		// not lookaheadsafe and different week, do nothing
		if (!$this->lookaheadSafe && $currentWeekYear != $wantedWeekYear) {
			return $this->data;
		}

		// lock if semaphores are usable
		if (function_exists('sem_get')) {
			// create simple positive crc32 "hash" for class semaphore
			$sem_key = crc32(get_class($this));
			if ($sem_key < 0) {
				$sem_key *= -1;
			}
			// get semaphore id
			$sem_id = sem_get($sem_key);
			if ($sem_id === false) {
				throw new Exception("Could not get semaphore with key {$sem_key}");
			}
			// aquire semaphore (this does the locking!)
			if (sem_acquire($sem_id) === false) {
				throw new Exception('Could not acquire semaphore');
			}
		}

		// do parsing
		$this->dataParsed = true;
		$data = $this->parseDataSource();

		// release semaphore (although this should be done automatically on shutdown)
		if (function_exists('sem_get')) {
			sem_release($sem_id);
		}

		// return parsed data
		return $data;
	}

	// main methode which will be called
	// queries the datasource for the menu
	// if a filterword is detected the line will be marked
	// caches the results
	public function getMenuData() {
		global $date_GET;

		// query cache
		$this->cacheRead();

		// not valid or old data
		$this->data = $this->parse_check();

		// handle empty data special in cache
		if (empty($this->data)) {
			$this->data = VenueStateSpecial::None;
		}

		// save special data to cache
		if (getenv('CACHE_SPECIAL_DATA') && $this->isStateSpecial()
				&& !$this->dataFromCache && $this->dataParsed) {
			$this->cacheWrite();
		}

		// special state urlaub
		if ($this->data == VenueStateSpecial::Urlaub) {
			return '<span class="error">Geschlossen wegen Urlaub/Feiertag</span><br />';
		} elseif ($this->data == VenueStateSpecial::None) {
			$this->data = null;
		}

		// normalize unicode
		if (!\Normalizer::isNormalized($this->data)) {
			$this->data = \Normalizer::normalize($this->data);
		}

		// replace html breaks with newlines
		$this->data = str_replace(Strings::EXPLODE_NEWLINES, "\n", $this->data);

		// convert special chars
		$this->data = htmlspecialchars($this->data, ENT_HTML5 | ENT_DISALLOWED);

		// replace newlines with html breaks
		$this->data = str_replace(Strings::EXPLODE_NEWLINES, '<br>', $this->data);

		// prepare return
		$return = isset($_GET['minimal']) ? '<br>' : '';

		if (!empty($this->data)) {
			// not from cache? => write back
			if (!$this->dataFromCache && $this->dataParsed) {
				$this->cacheWrite();
			}

			$contentCutoffLength = getenv('CONTENT_CUTOFF_LENGTH');
			if (mb_strlen($this->data) > $contentCutoffLength) {
				$this->data = '<span>' . mb_substr($this->data, 0, $contentCutoffLength) . '</span>'
					. "<span id='content_cutoff_{$this->id}' style='display: none'>" . mb_substr($this->data, $contentCutoffLength) . '</span>'
					.  "<span id='content_toggler_{$this->id}'>.. <a href='javascript:void(0)' onclick='$(\"#content_cutoff_{$this->id}\").show(); $(\"#content_toggler_{$this->id}\").remove()'>anzeigen</a></span>";
			}

			$return .= "<div class='menu '>
					<a class='menuData dataSource color_inherit' href='{$this->dataSource}' target='_blank' title='Datenquelle' style='color: inherit ! important;'>Angebot:</a>
					<span> </span>
					<span class='menuData'>{$this->data}</span>
				</div>";

			if ($this->price || $this->menu) {
				// remove empty values
				if ($this->price) {
					if (!is_array($this->price)) {
						$this->price = array($this->price);
					}
					foreach ($this->price as &$price) {
						if (is_numeric($price)) {
                            $price = ContainerHelper::getInstance()->get(TextService::class)->cleanText($price);
							$price = str_replace(',', '.', $price);
							$price = ContainerHelper::getInstance()->get(FormatService::class)->formatMoney(floatval($price));
						} else if (is_array($price)) {
							foreach ($price as &$p) {
								$p = trim($p, "/., \t\n\r\0\x0B");
								$p = str_replace(',', '.', $p);
								if (!empty($p))
                                    $p = ContainerHelper::getInstance()->get(FormatService::class)->formatMoney(floatval($p));
							}
							// remove empty values
							$price = array_filter($price);
							if (count($price) > 1) {
								$price_imploded = implode(' / ', $price);
								if (!isset($_GET['minimal'])) {
									$price = "<a class='color_inherit' title='{$this->price_nested_info}' onclick='alert(\"{$this->price_nested_info}: {$price_imploded}\")' style='color: inherit ! important;'>({$price_imploded})<span class='raised'>i</span></a>";
								} else {
									$price = "<a class='color_inherit' title='{$this->price_nested_info}' style='color: inherit ! important;'>({$price_imploded})<span class='raised'>i</span></a>";
								}
							} else if (!empty($price)) {
								$price = '<span>' . reset($price) . '</span>';
							}
						}
					}
					$price = implode(' / ', array_filter($this->price));
					$return .= "Details: <b>{$price}</b> (<a href='{$this->menu}' class='color_inherit' target='_blank' style='color: inherit ! important;'>Speisekarte</a>)";
				} else {
					$return .= "Details: <a href='{$this->menu}' class='color_inherit' target='_blank' style='color: inherit ! important;'>Speisekarte</a>";
				}
			}
		}
		else {
			// do randomized ajax reloads (60 - 120 seconds) of menu data
			$reload_code = '
				<script type="text/javascript">
					function reload_' . $this->id . '() {
						var id = "' . $this->id . '_data";
						$("#" + id).html(\'<div class="throbber middle">Lade...</div>\');
						' . $this->get_ajax_venue_code($date_GET, false) . '
					}
					function set_rand_reload_' . $this->id . '() {
						setTimeout(function() {
							reload_' . $this->id . '();
						}, Math.floor((Math.random() * 60) + 60) * 1000);
					}
					set_rand_reload_' . $this->id . '();
				</script>
				<a href="javascript:void(0)" onclick="reload_' . $this->id . '()">aktualisieren</a>
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

		$string = "<div id='{$this->id}' class='venueDiv'>";

		// title
		$string .= "<span class='title' title='Homepage'><a href='{$this->url}' class='no_decoration' target='_blank'>{$this->title}</a></span>";
		if ($this->title_notifier) {
			$string .= "<span class='title_notifier'>{$this->title_notifier}</span>";
		}
		// address icon with route planner
		if ($this->addressLat && $this->addressLng) {
			$string .= "<a href='https://www.openstreetmap.org/directions?engine=graphhopper_foot&amp;route="
                . sprintf('%F', getenv('LOCATION_FALLBACK_LAT')) . ','
                . sprintf('%F', getenv('LOCATION_FALLBACK_LNG'))
                . ";{$this->addressLat},{$this->addressLng}' class='no_decoration lat_lng_link' target='_blank'>
				<div data-enhanced='true' class='ui-link ui-btn ui-icon-location ui-btn-icon-notext ui-btn-inline ui-shadow ui-corner-all' title='OpenStreetMap Route'>OpenStreetMap Route</div>
			</a>";
		}
		// vote icon
		if (!isset($_GET['minimal']) && ContainerHelper::getInstance()->get(\App\Service\VoteService::class)->votingDisplayAllowed(get_ip())) {
			$string .= "<div data-enhanced='true' class='ui-link ui-btn ui-icon-plus ui-btn-icon-notext ui-btn-inline ui-shadow ui-corner-all' onclick='vote_up(\"{$this->id}\")' title='Vote Up'>Vote Up</div>";
			$string .= "<div data-enhanced='true' class='ui-link ui-btn ui-icon-minus ui-btn-icon-notext ui-btn-inline ui-shadow ui-corner-all' onclick='vote_down(\"{$this->id}\")' data-role='button' data-inline='true' data-icon='minus' data-iconpos='notext' title='Vote Down'>Vote Down</div>";
		}

		// check no menu days
		$no_menu_day = false;
		$dayNr = date('w', $this->timestamp);
		foreach ((array)$this->no_menu_days as $day) {
			if ($dayNr == $day) {
				$dayName = getGermanDayName();
				$string .= "<br /><span class='bold'>Leider kein Mittagsmenü am {$dayName} :(</span><br />";
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
					<div id="' . $this->id . '_data">
						<script type="text/javascript">
							' . $this->get_ajax_venue_code($date_GET) . '
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
		// strip week counter
		$string = preg_replace('/kw[\d]{2}/i', '', $string);
		// get dates to check out of string
		preg_match('/[\d]+\.(.)+(-|—|–|bis)(.)*[\d]+\.(.)+/u', $string, $date_check);
		if (empty($date_check) || !isset($date_check[0])) {
			return;
		}
		$date_check = explode_by_array(array('-', '—', '–', 'bis'), $date_check[0]);
		$date_check = array_map('trim', $date_check);
		$date_start = ContainerHelper::getInstance()->get(TimeService::class)->strtotimep($date_check[0], $start_format, $timestamp);
		$date_end   = ContainerHelper::getInstance()->get(TimeService::class)->strtotimep($date_check[1], $end_format, $timestamp);
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
		$string = str_replace([ "'", '"', '`', '´', ' ' ], '', $string);

		return (
			mb_substr_count($string, 'außer feiertag') ? 0 : mb_substr_count($string, 'feiertag') +
			mb_substr_count($string, 'urlaub') +
			mb_substr_count($string, 'weihnacht') +
			mb_substr_count($string, 'oster') +
			mb_substr_count($string, 'himmelfahrt') +
			mb_substr_count($string, 'geschlossen') +
			mb_substr_count($string, 'pfingsten') +
			mb_substr_count($string, 'pfingstmontag') +
			mb_substr_count($string, 'fronleichnam') +
			mb_substr_count($string, 'ruhetag')
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
			mb_substr_count($string, 'topfenstrudel') +
			mb_substr_count($string, 'fruchtsalat') +
			mb_substr_count($string, 'zwetschkenfleck') +
			mb_substr_count($string, 'zwetschkentraum') +
			mb_substr_count($string, 'schwarzwälderkirsch') +
			mb_substr_count($string, 'topfenwelle')
		);
	}

	protected function parse_foods_inbetween_days($data, $string_next_day,
			$string_last_day_next = [], $newline_replacer = "\n", $add_food_counts = true) {
		$today_variants = $this->get_today_variants();

		// convert next_day to an array
		if (!is_array($string_next_day)) {
			$string_next_day = [ $string_next_day ];
		}
		// convert last_day_next to an array
		if (!is_array($string_last_day_next)) {
			$string_last_day_next = [ $string_last_day_next ];
		}

		// replace some strings that may interfer with day parsing
		$data = str_replace([ 'Montag-Samstag', 'Montag-Freitag' ], '', $data);

		foreach ($today_variants as $today) {
			//error_log("looking for {$today}");
			$posStart = ContainerHelper::getInstance()->get(TextService::class)->strposAfter($data, $today);
			// set date and stop if match found
			if ($posStart !== false) {
				//error_log("'${today}' found on pos ${posStart}");
				break;
			}
		}
		if ($posStart === false) {
			//error_log('no start found');
			return;
		}
		$posEnds = [];
		foreach ($string_next_day as $tomorrow) {
			$posEnd = mb_stripos($data, $tomorrow, $posStart);
			// stop if match found
			if ($posEnd !== false) {
				//error_log("'${tomorrow}' found on pos ${posEnd}");
				$posEnds[] = $posEnd;
			}
		}
		//error_log("'${tomorrow}' search returned ${posEnd}");
		// last day of the week (array of strings)
		if (empty($posEnds)) {
			foreach ($string_last_day_next as $last_day_next) {
				$posEnd = mb_stripos($data, $last_day_next, $posStart);
				if ($posEnd !== false) {
					//error_log("'${last_day_next}' found on pos ${posEnd}");
					$posEnds[] = $posEnd;
				}
			}
		}
		// use whole string if no end was found or specified
		if (empty($posEnds)) {
			$posEnds = [mb_strlen($data)];
			//error_log('no end found');
		 }
		//return error_log(min($posEnds)) && false;

		$data = mb_substr($data, $posStart, min($posEnds) - $posStart);
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

		//~ return error_log($data) && false;

		// menu magic via common parser helper (auto numbering and stuff)
		return $this->parse_foods_helper($data, $newline_replacer, $prices, true, true, false, true,
				$add_food_counts);
	}

	private function parse_foods_helper($dataTmp, $newline_replacer, &$prices = [],
			$end_on_friday = true, $one_price_per_food = true, $use_weekday_feature = false,
			$check_holiday_counts = true, $add_food_counts = true
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
			'1 ', '2 ', '3 ', '4 ',
		];

		$regex_price = '/[0-9,\. ]*(€|eur|euro|tagesteller|fischmenü)+[ ]*[0-9,\. ]*/iu';

		// remove multiple newlines
		$dataTmp = preg_replace("/(\n)+/i", "\n", $dataTmp);
		$dataTmp = trim($dataTmp);
		//return error_log($dataTmp);
		// split per new line
		$foods = explode_by_array([ "\n", "\r" ], $dataTmp);
		//~ return error_log(print_r($foods, true)) && false;

		// immediately return if we only have 1 element
		/*if (count($foods) == 1)
			return $dataTmp;*/

		foreach ($foods as $food) {
            $food = ContainerHelper::getInstance()->get(TextService::class)->cleanText($food);

			if (empty($food)) {
				continue;
			}

			// price entry
			if (
				(!$one_price_per_food || ($foodCount == ($weekday+1) || !$use_weekday_feature)) &&
				//!empty($data) && // FIXME: why does it need to be empty?
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
				!ContainerHelper::getInstance()->get(TextService::class)->stringsExist($food, $number_markers) &&
				(mb_strlen($food) <= 5 ||
					mb_strlen(count_chars($food, 3)) <= 5 ||
                    ContainerHelper::getInstance()->get(TextService::class)->stringsExist($food, [/* place to add noise keywords */], true))
			) {
				//~ error_log("skip ${food}");
				continue;
			// keywords indicating end
			} else if (
				!$foodDone &&
                ContainerHelper::getInstance()->get(TextService::class)->stringsExist($food, [ 'Allergen', 'Tisch reservieren', 'Menü:' ]) || ($end_on_friday && ContainerHelper::getInstance()->get(TextService::class)->stringsExist($food, [ 'Freitag' ]))
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
				//~ error_log("got first food part with '{$food}'");
			// second part of menu
			} else if (
				!$foodDone &&
				($foodCount == ($weekday+1) || !$use_weekday_feature) /*&&
				!empty($data)*/ // don't use this, otherwise we need a soup
			) {
				//~ error_log("got second food part with '{$food}'");
				$data_lines = explode_by_array($newline_replacer, $data);
				// do newline replacers when the output string is not empty
				// OR whole menu contains a number marker and new food doesn't (avoids problems with menus stretching over multiple lines)
				if (!empty($data) || (ContainerHelper::getInstance()->get(TextService::class)->stringsExist((string)$data, $number_markers)
						&& !ContainerHelper::getInstance()->get(TextService::class)->stringsExist($food, $number_markers))) {
					// we are continuing the old food here
					if (mb_strpos($food, 'mit')  === 0 || ContainerHelper::getInstance()->get(TextService::class)->endsWith($data, 'mit')
							|| mb_strpos($food, 'dazu') === 0 || ContainerHelper::getInstance()->get(TextService::class)->endsWith($data, 'dazu')
							|| mb_strpos($food, 'und')  === 0 || ContainerHelper::getInstance()->get(TextService::class)->endsWith($data, 'und')
							|| mb_strpos($food, 'auf')  === 0 || ContainerHelper::getInstance()->get(TextService::class)->endsWith($data, 'auf')
							|| mb_strpos($food, ',')  === 0 || ContainerHelper::getInstance()->get(TextService::class)->endsWith($data, ',')) {
						//~ error_log("continue old food with '{$food}'");
						$data .= ' ';
					// use default newline replacer
					} else {
						//~ error_log("default food '{$food}'");
						$data .= $newline_replacer;
					}
				}
				$data .= $food;
			}
			//error_log($food);
		}
		//~ return error_log($data) && false;

		$foods = explode_by_array([ "\n", "\r" ], $data);
		//return error_log(print_r($foods, true)) && false;

		foreach ($foods as &$food) {
			// strip price infos from foods
			$food = preg_replace($regex_price, ' ', $food);
			// replace common numbering strings (if they start the line)
			foreach ($number_markers as $number_marker) {
				if (ContainerHelper::getInstance()->get(TextService::class)->startsWith($food, $number_marker)) {
					$food = mb_substr($food, mb_strlen($number_marker));
				}
			}
            $food = ContainerHelper::getInstance()->get(TextService::class)->cleanText($food);
		}
		unset($food);
		//return error_log(print_r($foods, true)) && false;

		// remove empty values
		$foods = array_filter($foods);
		//return error_log(print_r($foods, true)) && false;

		// add food counts: we try to detect different main foods, starters and desserts
		// automatically here
		if ($add_food_counts) {
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
		}

		$data = implode("\n", $foods);
		//return error_log($data) && false;

		// check if holiday
		if ($check_holiday_counts && $this->get_holiday_count($data)) {
			return VenueStateSpecial::Urlaub;
		}

        return ContainerHelper::getInstance()->get(TextService::class)->cleanText($data);
	}

	protected function parse_foods_independant_from_days($dataTmp, $newline_replacer,
			&$prices = null, $end_on_friday = true, $one_price_per_food = true,
			$check_holiday_counts = true
	) {
		return $this->parse_foods_helper($dataTmp, $newline_replacer, $prices, $end_on_friday,
				$one_price_per_food, true, $check_holiday_counts);
	}

	protected function parse_facebook($page_id) {
		$data = null;
		foreach (ContainerHelper::getInstance()->get(FacebookService::class)->allPosts($page_id) as $post) {
			if (!isset($post['message']) || !isset($post['created_time'])
					|| !isset($post['id'])) {
				continue;
			}

			// ignore posts other than from the wanted page
			if (explode('_', $post['id'])[0] != $page_id) {
				continue;
			}

			// get wanted data out of post
			$message = $post['message'];
			$created_time = $post['created_time'];

			// clean/adapt data
			$message = trim($message, "\n ");
			$created_time = strtotime($created_time);

			//error_log($message);

			// not from today, skip
			if (date('Ymd', $created_time) != date('Ymd', $this->timestamp)) {
				continue;
			}

			//error_log($message);

			// nothing mittags-relevantes found
			$words_relevant = [
				'Mittagspause', 'Mittagsmenü', 'was gibts', 'bringt euch', 'haben wir', 'gibt\'s',
				'Essen', 'Mahlzeit', 'Vorspeise', 'Hauptspeise', 'bieten euch', 'bis gleich',
				'gibt', 'servieren euch', 'Bis gleich', 'schmecken', 'freuen uns auf euch',
				'neue Woche', 'wartet schon auf euch', 'freuen sich auf euch', 'erwarten euch',
				'suppe', 'Suppe', 'heute', 'Mittagsteller',
			];
			if (!ContainerHelper::getInstance()->get(TextService::class)->stringsExist($message, $words_relevant)) {
				continue;
			}

			// use whole fp post
			$data = str_replace("\n", "<br />", $message);
		}

		// check if holiday
		if ($this->get_holiday_count($data)) {
			return VenueStateSpecial::Urlaub;
		}

		return $data;
	}
}
