<?php

require_once(__DIR__ . '/includes.php');

define('NEARPLACES_CACHE', TMP_PATH . 'nearplaces_cache.json');
define('DETAILS_CACHE', TMP_PATH . 'details_cache.json');
define('LOG_API_REQUESTS', true);

// imitate google api data for custom venues
// use negative ids to distinguish between google nearplace
// and custom nearplace venue entries
$custom_venues = array(
	array(
		'id' => -1,
		'geometry' => array(
			'location' => array(
				'lat' => 48.192134,
				'lng' => 16.348653,
			),
		),
		'name' => 'le Pho',
		'website' => 'http://www.le-pho.at/',
		'reference' => -1,
	),
	array(
		'id' => -2,
		'geometry' => array(
			'location' => array(
				'lat' => 48.191429,
				'lng' => 16.347603,
			),
		),
		'name' => 'Disco Volante',
		'website' => 'http://www.disco-volante.at/',
		'reference' => -1,
	),
	array(
		'id' => -3,
		'geometry' => array(
			'location' => array(
				'lat' => 48.197427,
				'lng' => 16.359181,
			),
		),
		'name' => 'Kuishimbo',
		'website' => 'https://www.facebook.com/pages/Kuishimbo/67343688168',
		'reference' => -1,
	),
	array(
		'id' => -4,
		'geometry' => array(
			'location' => array(
				'lat' => 48.198927,
				'lng' => 16.364725,
			),
		),
		'name' => 'Kojiro - Sushi-Bar',
		'website' => 'https://plus.google.com/106777516565797933298/about',
		'reference' => -1,
	),
);

// searches for venue data in the nearplaces cache
// returns null if nothing found
function nearplace_details_cache_search($name) {
	$data = details_cache_read(null, null);
	foreach ((array)$data as $dataset) {
		foreach ((array)$dataset as $venue) {
			if (isset($venue['name']) && mb_stripos($venue['name'], $name) !== false)
				return $venue;
		}
	}
	return null;
}

// searches for venue data in the nearplaces cache
// returns null if nothing found
function nearplace_cache_search($name) {
	$data = nearplace_cache_read(null, null, null);
	foreach ((array)$data as $dataset) {
		foreach ((array)$dataset as $venue) {
			if (isset($venue['name']) && mb_stripos($venue['name'], $name) !== false)
				return $venue;
		}
	}
	return null;
}

/*
 * reads an entry from the details cache
 */
function details_cache_read($id, $reference) {
	if (!file_exists(DETAILS_CACHE))
		return null;
	else {
		$data = file_get_contents(DETAILS_CACHE);
		if (empty($data))
			return null;
		$data = json_decode($data, true);
		if ($data === null)
			return null;
	}

	// if id and reference are null, return all data
	if ($id === null && $reference === null)
		return $data;

	// if younger than 1 week, same id OR reference => cache-hit
	foreach ((array)$data as $cache_key => $cache_entry) {
		$cache_key = explode('|', $cache_key);
		if (
			count($cache_key) == 3 &&
			$cache_key[2] >= strtotime('-1 week') &&
			($cache_key[0] == $id || $cache_key[1] == $reference)
		) {
			return $cache_entry;
		}
	}
	return null;
}
/*
 * writes an entry in the details cache
 */
function details_cache_write($id, $reference, $cache_entry) {
	if (!file_exists(DETAILS_CACHE))
		$data = array();
	else {
		$data = file_get_contents(DETAILS_CACHE);
		if (empty($data))
			$data = array();
		$data = json_decode($data, true);
		if ($data === null)
			$data = array();
	}

	$cache_key = array(
		'id'        => $id,
		'reference' => $reference,
		'timestamp' => time(),
	);
	$cache_key = implode('|', $cache_key);
	$data[$cache_key] = $cache_entry;
	$data = json_encode($data);
	file_put_contents(DETAILS_CACHE, $data);
}
/*
 * reads an entry from the nearplace cache for full searches
 * returns the data or null on error/empty cache
 */
function nearplace_cache_read($lat, $lng, $radius) {
	if (!file_exists(NEARPLACES_CACHE))
		return null;
	else {
		$data = file_get_contents(NEARPLACES_CACHE);
		if (empty($data))
			return null;
		$data = json_decode($data, true);
		if ($data === null)
			return null;
	}

	// if parameters are null, return all
	if ($lat === null && $lng === null && $radius === null)
		return $data;

	// if younger than 1 week, radius +- 100 m and lat/lng distance +- 100 m, return cache entry
	foreach ((array)$data as $cache_key => $cache_entry) {
		$cache_key = explode('|', $cache_key);
		if (
			count($cache_key) == 4 &&
			$cache_key[3] >= strtotime('-1 week') &&
			abs($cache_key[2] - $radius) <= 100 &&
			distance($cache_key[0], $cache_key[1], $lat, $lng, false) * 1000 <= 100
		) {
			// cache built at night, but day now => counts as miss, because opennow is used when querying
			$hour = (int)date('G');
			$cache_hour = (int)date('G', $cache_key[3]);
			if (
				in_range($hour, 7, 17) && in_range($cache_hour, 7, 17) || // day
				!in_range($hour, 7, 17) && !in_range($cache_hour, 7, 17) || // night
				in_range($cache_hour, 7, 17) // but if cache from day also valid in night
			) {
				return $cache_entry;
			}
		}
	}
	return null;
}
/*
 * writes an entry in the nearplace cache for full searches
 */
function nearplace_cache_write($lat, $lng, $radius, $cache_entry) {
	if (!file_exists(NEARPLACES_CACHE))
		$data = array();
	else {
		$data = file_get_contents(NEARPLACES_CACHE);
		if (empty($data))
			$data = array();
		$data = json_decode($data, true);
		if ($data === null)
			$data = array();
	}

	$cache_key = array(
		'lat'       => $lat,
		'lng'       => $lng,
		'radius'    => $radius,
		'timestamp' => time(),
	);
	$cache_key = implode('|', $cache_key);
	$data[$cache_key] = $cache_entry;
	$data = json_encode($data);
	file_put_contents(NEARPLACES_CACHE, $data);
}

function handle_api_response($api_response) {
	// check status flag
	if (!isset($api_response['status']) || $api_response['status'] != 'OK')
		return null;

	// save next_page_token in session for future queries
	if (!empty($api_response['next_page_token']))
		$_SESSION['nearplaces']['next_page_token'] = trim($api_response['next_page_token']);
	// delete if not found to avoid problems with old session stuff
	else if (isset($_SESSION['nearplaces']['next_page_token']))
		unset($_SESSION['nearplaces']['next_page_token']);

	return $api_response;
}

function build_response($lat_orig, $lng_orig, $api_response) {
	$response = array();

	foreach ((array)$api_response as $result) {
		// important stuff missing, continue
		if (!isset($result['name']) || !isset($result['geometry']['location']))
			continue;

		// clean name from unwanted stuff
		$name = trim(str_ireplace(array(
			'silberwirt', 'margareta', 'altes fassl', 'aus:klang', 'delicious monster', 'haas beisl', 'Haasbeisl', 'kunsthallencafé', 'taste of india',
			'zum schwarzer adler', 'schlossquadrat', 'Nam Nam', 'Restaurant Waldviertlerhof Josef Krenn Ges.m.b.H.',
			'Schloss Schönbrunn Konzerte', 'betriebs . nfg. keg', 'ges.m.b.h', 'ges.mbh', 'gesmbh', 'gmbh', 'm.b.h', 'immobilien', 'e.u', 'weinbar', 'gesellschaft',
			'gasthaus', 'brauerei', 'Betriebs . Nfg. KEG', 'Co KG', 'betriebs', 'Billa AG', 'Billa Aktien', 'Brabenetz & Dirnwöber',
			'm. islam kg', 'andreas gruber', 'Schnaps- Club Hofstöckl', 'Wien - Margit Köffler', 'Göd Manfred', 'keg', 'hans rudolf siegl',
			'fairleih', 'eventausstattung', 'tchibo / eduscho filiale', 'Billa', 'Penny Markt', 'Osteria Vinoteca Panarea', 'Little Stage',
			'Zum Stöger', 'Lidl Austria', 'Inh. Kaya Aydin', 'SPAR Österreichische Warenhandels-AG', 'WerkzeugH', '- BAR - ENOTECA', 'Motto Club-Restaurant-Bar',
			'Waldviertlerhof', 'conceptspace', 'Public-theplacetobe', 'Ankerbrot AG', 'International Limited', 'Espresso Italiano', 'Oxy Zentrum', 'Pizza Hotline',
			'Burger Bring', 'LA VITA È BELLA', 'Rori\'s Finest Sweets', 'Pizza Da Capo', 'Motto', 'Bäckerei Cafe Felzl', 'Cafe 60', 'Vinothek Pub Klemo', 'Café Standard',
			'Collina Vienna', 'Cafe Willendorf', 'Pizzeria La Carne', 'Deli', 'Il Cantuccino', 'Mokador Caffe', 'Home Made - Grocery & Café', 'Schnipi Schnitzel- u Pizzazustellung',
			'Rosa Lila Tip', 'Senhor Vinho', 'Gergely\'s', 'Chicken King & Makara Noodle', 'Vinoteca Tropea - Vienna', 'Schlupfwinkel Abendbeisl', 'Andino', 'Lehmberg',
			'Battello', 'Aromat', 'MINIRESTAURANT', 'Natraj - indischer Lieferservice', 'Bonbon et Chocolat', 'Cafe Restaurant Horvath', 'Finkh', 'Brass Monkey',
			'Indisches Restaurant Mirchi', 'Cafe Jelinek', 'Fleischerei Friedrich Szabo', 'Mami\'s Möhspeis', 'Fleischboutique', 'Celeste Cafe', 'Spar-supermarkt',
			'Radatz Filiale Wiedner Hauptstraße', 'Erste Wiener Katzenambulanz Mag. med vet Ingrid Harant', 'Naturprodukte Wallner', 'NIPPON YA Handels',
			'BOBBY\'S Foodstore - Your British and American Foodstore', 'NH Atterseehaus', 'Restaurant Schwarzer Adler', 'pentahotel Vienna', 'Rudi\'s Beisl',
			'Café Rüdigerhof', 'Cafe Siebenbrunnen', 'Café Wortner Kaffeehaus', 'Wieden Bräu', 'Cafe Savoy', 'Frascati Pizzeria Gelateria', 'Café Ritter',
			'Schick Hotel Erzherzog Rainer', 'Restaurant Wiener Wirtschaft', 'Cafe Drechsler', 'Johnny\'s Pub', 'Restaurant Goldene Glocke', 'Tchibo Filiale',
			'Vinothek La Cave', 'Bar Tabacchi', 'PizzaMann', 'Hofer', 'Weinschenke', 'Trattoria Pizzeria Blaue Lagune', 'Délices du midi', 'Conti & Co',
			'Yak und Yeti', 'LioUnge', 'Pizza John', 'Trzesniewski'
		), '', $result['name']), ',.;_.-:"& ');
		$name_clean_check = trim(str_ireplace(array(
			'restaurant', 'ristorante'
		), '', $name));

		// name empty
		if (empty($name) || empty($name_clean_check))
			continue;

		$names[$name] = true;

		$lat           = str_replace(',', '.', trim($result['geometry']['location']['lat']));
		$lng           = str_replace(',', '.', trim($result['geometry']['location']['lng']));
		$rating        = isset($result['rating']) ? $result['rating'] : null;
		$id            = isset($result['id']) ? $result['id'] : null;
		$reference     = isset($result['reference']) ? $result['reference'] : null;
		$maps_href     = htmlspecialchars("https://maps.google.com/maps?dirflg=r&saddr=$lat_orig,$lng_orig&daddr=$lat,$lng");
		$name_url_safe = urlencode($name);
		$name_escaped  = htmlspecialchars($name, ENT_QUOTES);
		$name_escaped  = str_replace("'", '', $name_escaped);

		$href = "<a href='javascript:void(0)' onclick='handle_href_reference_details(\"{$id}\", \"{$reference}\", \"{$name_url_safe}\", 0)' title='Homepage'>{$name_escaped}</a>";
		$actions = "<a href='$maps_href' target='_blank'><span class='icon sprite sprite-icon_pin_map' title='Google Maps Route'></span></a>";
		if (show_voting())
			$actions .= "<a href='javascript:void(0)' onclick='vote_up(\"{$name_escaped}\")'><span class='icon sprite sprite-icon_hand_pro' title='Vote Up'></span></a>"
			          . "<a href='javascript:void(0)' onclick='vote_down(\"{$name_escaped}\")'><span class='icon sprite sprite-icon_hand_contra' title='Vote Down'></span></a>";

		$response[] = array(
			'name' => $name,
			$href,
			round(distance($lat_orig, $lng_orig, $lat, $lng, false) * 1000),
			(!$rating) ? '-' : $rating,
			$actions,
		);
	}

	return $response;
}

function remove_doubles($response) {
	$names_found = array();
	$response_new = array();

	foreach ($response as $entry) {
		if (!in_array($entry['name'], $names_found)) {
			$names_found[] = $entry['name'];
			$response_new[] = $entry;
		}
	}

	// remove name attribute
	foreach ($response_new as &$entry) {
		unset($entry['name']);
	}
	unset($entry);

	return $response_new;
}

function nextpage_search($lat, $lng, $radius, $sensor, $opennow=false, $rankby=null) {
	global $GOOGLE_API_KEYS;

	shuffle($GOOGLE_API_KEYS);

	// get next_page_token from search before
	if (!isset($_SESSION['nearplaces']['next_page_token']))
		return null;
	$next_page_token = $_SESSION['nearplaces']['next_page_token'];

	// query api
	foreach ($GOOGLE_API_KEYS as $api_key) {
		if (LOG_API_REQUESTS)
			error_log("@nextpage_search: query api with key $api_key");
		$api_url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?key=$api_key&location=$lat,$lng&radius=$radius&rankby=$rankby&sensor=$sensor&types=restaurant|food&language=de";
		if ($opennow)
			$api_url .= '&opennow';
		$api_url .= "&pagetoken=$next_page_token";
		$api_response = @file_get_contents($api_url);
		if ($api_response === false)
			continue;
		$api_response = json_decode($api_response, true);
		break;
	}

	return handle_api_response($api_response);
}

function nearbysearch($lat, $lng, $radius, $sensor, $opennow=false, $rankby=null) {
	global $GOOGLE_API_KEYS, $custom_venues;

	shuffle($GOOGLE_API_KEYS);

	// query api
	foreach ($GOOGLE_API_KEYS as $api_key) {
		if (LOG_API_REQUESTS)
			error_log("@nearbysearch: query api with key $api_key");
		$api_url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?key=$api_key&location=$lat,$lng&radius=$radius&rankby=$rankby&sensor=$sensor&types=restaurant|food&language=de";
		if ($opennow)
			$api_url .= '&opennow';
		$api_response = @file_get_contents($api_url);
		if ($api_response === false)
			continue;
		$api_response = json_decode($api_response, true);
		break;
	}

	// add custom venues that are in reach
	if (isset($api_response['results'])) {
		foreach ((array)$custom_venues as $venue) {
			$lat_venue = isset($venue['geometry']['location']['lat']) ? $venue['geometry']['location']['lat'] : null;
			$lng_venue = isset($venue['geometry']['location']['lng']) ? $venue['geometry']['location']['lng'] : null;
			if (distance($lat_venue, $lng_venue, $lat, $lng, false) <= $radius)
				$api_response['results'][] = $venue;
		}
	}

	return handle_api_response($api_response);
}

function details($id, $reference, $sensor) {
	global $GOOGLE_API_KEYS, $custom_venues;

	// negative id => custom nearplace venue
	if ($id < 0) {
		$api_response = null;
		foreach ((array)$custom_venues as $venue) {
			if ($venue['id'] == $id && $reference == -1) {
				$api_response['result'] = $venue;
				$api_response['status'] = 'OK';
				break;
			}
		}
	}
	// query api
	else {
		shuffle($GOOGLE_API_KEYS);
		foreach ($GOOGLE_API_KEYS as $api_key) {
			if (LOG_API_REQUESTS)
				error_log("@details: query api with key $api_key");
			$api_url = "https://maps.googleapis.com/maps/api/place/details/json?key=$api_key&reference=$reference&sensor=$sensor&language=de";
			$api_response = @file_get_contents($api_url);
			if ($api_response === false)
				continue;
			$api_response = json_decode($api_response, true);
			break;
		}
	}

	return handle_api_response($api_response);
}

function nearbysearch_full($lat, $lng, $radius, $sensor) {
	$api_results = nearplace_cache_read($lat, $lng, $radius);
	if ($api_results === null) {
		$api_results = array();

		// default nearbysearch
		$api_response = nearbysearch($lat, $lng, $radius, $sensor);
		$api_results = array_merge($api_results, (array)$api_response['results']);
		while(1) {
			sleep(2);
			$api_response = nextpage_search($lat, $lng, $radius, $sensor);
			if (!$api_response)
				break;
			else
				$api_results = array_merge($api_results, (array)$api_response['results']);
		}

		// nearby search with opennow
		// UPDATE doesn't really make sense because of caching
		/*$api_response = nearbysearch($lat, $lng, $radius, $sensor, true);
		$api_results = array_merge($api_results, (array)$api_response['results']);
		while(1) {
			sleep(2);
			$api_response = nextpage_search($lat, $lng, $radius, $sensor, true);
			if (!$api_response)
				break;
			else
				$api_results = array_merge($api_results, (array)$api_response['results']);
		}*/

		if ($api_results !== null)
			nearplace_cache_write($lat, $lng, $radius, $api_results);
	}
	return $api_results;
}

?>