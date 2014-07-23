<?php

require_once('includes.php');
//require_once('CacheHandler_File.php');
require_once('CacheHandler_MySql.php');

abstract class VenueStateSpecial {
	const Urlaub = 12345;
}

/*
 * Venue Class
 */
abstract class FoodGetterVenue {
	protected $title = null;
	protected $title_notifier = null; // presented small highlighted next to the title
	protected $addressLat = null;
	protected $addressLng = null;
	protected $url = null;
	protected $dataSource = null;
	protected $data = null;
	protected $date = null;
	protected $price = null;
	protected $statisticsKeyword = null;
	protected $no_menu_days = array(); // 0 sunday - 7 saturday
	protected $lookaheadSafe = false; // future lookups possible? otherwise only current week (e.g. because dayname check)
	protected $dataFromCache = false;
	protected $price_nested_info = null;

	// constructor
	// set date offset via get parameter
	function __construct() {
		global $dateOffset, $timestamp;

		$this->dateOffset = $dateOffset;
		$this->timestamp = $timestamp;

		// fix wrong lat/lng values
		$this->addressLat = str_replace(',', '.', $this->addressLat);
		$this->addressLng = str_replace(',', '.', $this->addressLng);
	}

	// overwrite in inherited class
	// should parse datasource, set data, date, price, cache it and return it
	abstract protected function parseDataSource();
	abstract protected function parseDataSource_fallback();

	// overwrite in inherited class
	// should check if the data is from today
	// used for caching
	abstract public function isDataUpToDate();

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

	// main methode which will be called
	// queries the datasource for the menu
	// if a filterword is detected the line will be marked
	// caches the results
	public function getMenuData() {
		global $cacheDataExplode;
		global $cacheDataIgnore;
		global $explodeNewLines;

		// query cache
		$this->cacheRead();

		// not valid or old data
		if (!$this->data || !$this->isDataUpToDate()) {
			// avoid querying other week than current
			// fixes cache problems
			$currentWeekYear = date('W/Y');
			$wantedWeekYear = date('W/Y', $this->timestamp);
			//error_log(date('W', $this->timestamp));
			if (
				($currentWeekYear == $wantedWeekYear) ||	// current week only
				($this->lookaheadSafe && $currentWeekYear < $wantedWeekYear) || // lookaheadsafe menus work with future weeks also
				($this->lookaheadSafe && $wantedWeekYear == 1) // because of ISO 8601 last week of year is sometimes returned as 1
			)
				if (!$this->parseDataSource()) {
					$this->parseDataSource_fallback();
				}
		}

		// check if data suggests that venue is closed
		/*if (stringsExist($data, $cacheDataDelete))
			$data = null;*/

		// special state urlaub
		if ($this->data == VenueStateSpecial::Urlaub)
			return '<br /><span class="error">Zurzeit geschlossen wegen Urlaub</span><br />';

		// mark each ingredient by an href linking to search
		$data = create_ingredient_hrefs($this->data, $this->statisticsKeyword, 'menuData');

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
		if ($currentWeek > $wantedWeek && !$data)
			$data = null;

		if (!empty($data) && $this->isDataUpToDate()) {
			// not from cache? => write back
			if (!$this->dataFromCache)
				$this->cacheWrite();

			// dirty encode & character
			// can't use htmlspecialchars here, because we need those ">" and "<"
			$data = str_replace("&", "&amp;", $data);

			$angebot_link = '<a class="menuData dataSource" href="' . $this->dataSource . '" target="_blank" title="Datenquelle">Angebot:</a>';
			$return .= "<div class='menu'>$angebot_link <span class='menuData'>" . $data . "</span></div>";

			if ($this->price && strpos($data, '€') === FALSE) {
				if (!is_array($this->price)) {
					$this->price = array($this->price);
				}
				foreach ($this->price as &$price) {
					if (!is_array($price)) {
						$price = trim($price, ' ,.');
						$price = str_replace(',', '.', $price);
						$price = money_format('%.2n', $price);
					}
					else {
						foreach ($price as &$p) {
							$p = trim($p, ' ,./');
							$p = str_replace(',', '.', $p);
							$p = money_format('%.2n', $p);
						}
						if (count($price) > 1)
							$price = '<span title="' . $this->price_nested_info . '">(' . implode(' / ', $price) . ')</span>';
						else
							$price = '<span>' . reset($price) . '</span>';
					}
				}
				$price = implode(' / ', $this->price);
				$return .= "Preis: <b>$price</b>";
			}
		}
		else {
			$return .= '<br /><span class="error">Leider nichts gefunden :(</span><br />';
			$return .= "Speisekarte: <a href='$this->dataSource' target='_blank'>Link</a>";
		}

		return $return;
	}

	// gets menu data and prints it in a nice readable form
	public function __toString() {
		global $date_GET;

		$string = '';

		$CSSid = 'id_' . md5($this->dataSource);

		// if minimal (JS free) site requested => show venues immediately
		if (!isset($_GET['minimal']))
			$attributeStyle = 'display: none';
		else
			$attributeStyle = '';

		$string .= "<div id='$CSSid' class='venueDiv' style='$attributeStyle'>";
		// hidden lat/lng spans
		if (!isset($_GET['minimal'])) {
			$string .= '<span class="hidden lat">' . $this->addressLat . '</span>';
			$string .= '<span class="hidden lng">' . $this->addressLng . '</span>';
		}

		// title
		$string .= "<span class='title' title='Homepage'><a href='$this->url' target='_blank'>$this->title</a></span>";
		if ($this->title_notifier)
			$string .= "<span class='title_notifier'>$this->title_notifier</span>";
		// address icon with route planner
		if ($this->addressLat && $this->addressLng) {
			$string .= "<a class='lat_lng_link' href='https://maps.google.com/maps?dirflg=r&amp;saddr=@@lat_lng@@&amp;daddr=" . $this->addressLat . "," . $this->addressLng . "' target='_blank'><span class='icon sprite sprite-icon_pin_map' title='Google Maps Route'></span></a>";
		}
		// vote icon
		if (!isset($_GET['minimal']) && show_voting()) {
			$voteString = addslashes($this->title);
			$string .= '<a href="javascript:void(0)" onclick="vote_up(\'' . $voteString . '\')"><span class="icon sprite sprite-icon_hand_pro" title="Vote Up"></span></a>';
			$string .= '<a href="javascript:void(0)" onclick="vote_down(\'' . $voteString . '\')"><span class="icon sprite sprite-icon_hand_contra" title="Vote Down"></span></a>';
		}

		// check no menu days
		$no_menu_day = false;
		$dayNr = date('w', $this->timestamp);
		foreach ((array)$this->no_menu_days as $day) {
			if ($dayNr == $day) {
				$dayName = getGermanDayName();
				$string .= "<br /><span class='error'>Leider kein Mittagsmenü am $dayName :(</span><br />";
				$string .= "Speisekarte: <a href='$this->dataSource' target='_blank'>Link</a>";
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
					<span id="' . $CSSid . '_data">
						<br />
						<img src="imagesCommon/loader.gif" width="160" height="24" alt="ladebalken" style="vertical-align: middle" />
					</span>
					<script type="text/javascript">
						head.ready("scripts", function() {
							if (jQuery.inArray("' . get_class($this) . '", venues_ajax_query) == -1) {
								venues_ajax_query.push("' . get_class($this) . '");
								$.ajax({
									type: "POST",
									url:  "venue.php",
									data: {
										"classname": "' . get_class($this) . '",
										"timestamp": "' . $this->timestamp . '",
										"dateOffset": "' . $this->dateOffset . '",
										"date": "'. $date_GET . '"
									},
									dataType: "json",
									success: function(result) {
										$("#' . $CSSid . '_data").html(result);
									},
									error: function() {
										var errMsg = $(document.createElement("span"));
										errMsg.attr("class", "error");
										errMsg.html("Fehler beim Abfragen der Daten :(");
										errMsg.prepend($(document.createElement("br")));
										$("#' . $CSSid . '_data").empty();
										$("#' . $CSSid . '_data").append(errMsg);
									}
								});
							}
						});
					</script>
				';
		}
		$string .= '</div>';

		return $string;
	}
}
?>