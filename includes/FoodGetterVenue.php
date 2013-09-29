<?php

require_once('includes.php');
//require_once('CacheHandler_File.php');
require_once('CacheHandler_MySql.php');

/*
 * Venue Class
 */
abstract class FoodGetterVenue {
	protected $title = null;
	protected $addressLat = null;
	protected $addressLng = null;
	protected $url = null;
	protected $dataSource = null;
	protected $data = null;
	protected $date = null;
	protected $price = null;
	protected $statisticsKeyword = null;
	protected $weekendMenu = 0; // 0: none, 1: saturday, 2: saturday + sunday
	protected $lookaheadSafe = false; // future lookups possible? otherwise only current week (e.g. because dayname check)
	protected $dataFromCache = false;

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
		if (!$this->isDataUpToDate() || !$this->data) {
			// avoid querying other week than current
			// fixes cache problems
			$currentWeek = date('W');
			$wantedWeek = date('W', $this->timestamp);
			if (
				($currentWeek == $wantedWeek) ||	// current week only
				($this->lookaheadSafe && $currentWeek < $wantedWeek)	// lookaheadsafe menus work with future weeks also
			)
				if (!$this->parseDataSource()) {
					$this->parseDataSource_fallback();
				}
		}

		// mark each ingredient by an href linking to search
		$data = create_ingredient_hrefs($this->data, $this->statisticsKeyword, 'menuData');

		// run filter
		$data = explode_by_array($explodeNewLines, $data);
		foreach ($data as &$dElement) {
			// remove 1., 2., stuff
			$foodClean = str_replace($cacheDataIgnore, '', $dElement);
			$foodClean = trim($foodClean);
			$foodClean = explode_by_array($cacheDataExplode, $foodClean);
		}
		$data = implode('<br />', $data);

		// prepare return
		$return = '';

		// old data and nothing found => don't display anything
		$currentWeek = date('W');
		$wantedWeek = date('W', $this->timestamp);
		if ($currentWeek > $wantedWeek && !$data)
			return '';

		if ($data && $this->isDataUpToDate()) {
			// vote icon
			if (!isset($_GET['minimal']) && show_voting()) {
				$voteString = addslashes($this->title);
				$return .= '<a href="javascript:void(0)" onclick="vote_up(\'' . $voteString . '\')"><span class="icon sprite sprite-icon_hand_pro" title="Vote Up"></span></a>';
				$return .= '<a href="javascript:void(0)" onclick="vote_down(\'' . $voteString . '\')"><span class="icon sprite sprite-icon_hand_contra" title="Vote Down"></span></a>';
			}

			// not from cache? => write back
			if (!$this->dataFromCache)
				$this->cacheWrite();

			// dirty encode & character
			// can't use htmlspecialchars here, because we need those ">" and "<"
			$data = str_replace("&", "&amp;", $data);

			$return .= "<div class='menu'>Angebot: <span class='menuData'>" . $data . "</span></div>";

			if ($this->price && strpos($data, '€') === FALSE) {
				if (!is_array($this->price)) {
					$this->price = array($this->price);
				}
				foreach ($this->price as &$price) {
					if (!is_array($price)) {
						$price = str_replace(',', '.', $price);
						$price = money_format('%.2n', $price);
					}
					else {
						foreach ($price as &$p) {
							$p = str_replace(',', '.', $p);
							$p = money_format('%.2n', $p);
						}
						$price = '<span title="Vorspeise oder Dessert / Vorspeise und Dessert">(' . implode(' / ', $price) . ')</span>';
					}
				}
				$price = implode(' / ', $this->price);
				$return .= "Preis: <b>$price</b>";
			}
		}
		else {
			if (isset($_GET['minimal']))
				$return .= '<br />';
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
		// statistics icon
		if ($this->statisticsKeyword) {
			$url = 'statistics.php?keyword=' . $this->statisticsKeyword . '&date=' . urlencode($date_GET);
			$string .= '<a href="' . htmlspecialchars($url) . '"><span class="icon sprite sprite-icon_chart_line" title="Statistik"></span></a>';
		}
		// address icon with route planner
		if ($this->addressLat && $this->addressLng) {
			$string .= "<a name='lat_lng_link' href='https://maps.google.com/maps?dirflg=r&saddr=@@lat_lng@@&daddr=" . $this->addressLat . "," . $this->addressLng . "' target='_blank'><span class='icon sprite sprite-icon_pin_map' title='Google Maps Route'></span></a>";
		}

		// no food on weekends
		if ((getGermanDayNameShort() == 'Sa' || getGermanDayNameShort() == 'So') &&
			$this->weekendMenu == 0) {
			$string .= '<br /><span class="error">Leider kein Mittagsmenü am Wochenende :(</span><br />';
			$string .= "Speisekarte: <a href='$this->dataSource' target='_blank'>Link</a>";
		}
		// no food on sundays
		else if (getGermanDayNameShort() == 'So' && $this->weekendMenu == 1) {
			$string .= '<br /><span class="error">Leider kein Mittagsmenü am Sonntag :(</span><br />';
			$string .= "Speisekarte: <a href='$this->dataSource' target='_blank'>Link</a>";
		}
		else {
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
