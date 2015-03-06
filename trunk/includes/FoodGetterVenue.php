<?php

require_once(__DIR__ . '/includes.php');
//require_once(__DIR__ . '/CacheHandler_File.php');
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
		/*$cache = new CacheHandler_File($this->dataSource, $this->timestamp);
		$cache->saveToCache($this->date, $this->price, $this->data);*/

		$cache = new CacheHandler_MySql($this->dataSource, $this->timestamp);
		$cache->saveToCache($this->date, $this->price, $this->data);
	}
	// reads data from the cache
	protected function cacheRead() {
		/*$cache = new CacheHandler_File($this->dataSource, $this->timestamp);
		$this->dataFromCache = $cache->getFromCache($this->date, $this->price, $this->data);*/

		$cacheNew = new CacheHandler_MySql($this->dataSource, $this->timestamp);
		$this->dataFromCache = $cacheNew->getFromCache($this->date, $this->price, $this->data);
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
				($currentWeekYear == $wantedWeekYear) ||	// current week only
				($this->lookaheadSafe && $currentWeekYear < $wantedWeekYear) || // lookaheadsafe menus work with future weeks also
				($this->lookaheadSafe && $wantedWeekYear == 1) // because of ISO 8601 last week of year is sometimes returned as 1
			)
				$this->parseDataSource();
		}

		// check if data suggests that venue is closed
		/*if (stringsExist($data, $cacheDataDelete))
			$data = null;*/

		$data = $this->data;

		// special state urlaub
		if ($data == VenueStateSpecial::Urlaub)
			return '<br /><span class="error">Zurzeit geschlossen wegen Urlaub</span><br />';
		else if ($data == VenueStateSpecial::UrlaubMaybe)
			return '<br /><span class="error">Vermutlich zurzeit geschlossen wegen Urlaub</span><br />';

		$data = create_ingredient_hrefs($data, $this->statisticsKeyword, 'menuData', false);

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

			// data found => increase cache to 1 year
			header_set_cache(31536000);

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
}
