<?php

require_once(__DIR__ . '/includes.php');

define('NEARPLACES_CACHE', TMP_PATH . 'nearplaces_cache.json');
define('DETAILS_CACHE', TMP_PATH . 'details_cache.json');
define('LOG_API_REQUESTS', false);

// imitate google api data for custom venues
// use negative ids to distinguish between google nearplace
// and custom nearplace venue entries
$custom_venues = [
	[
		'id' => -1,
		'geometry' => [
			'location' => [
				'lat' => 48.192134,
				'lng' => 16.348653,
			],
		],
		'name' => 'le Pho',
		'website' => 'http://www.le-pho.at/',
		'reference' => -1,
	],
	[
		'id' => -2,
		'geometry' => [
			'location' => [
				'lat' => 48.191429,
				'lng' => 16.347603,
			],
		],
		'name' => 'Disco Volante',
		'website' => 'http://www.disco-volante.at/',
		'reference' => -1,
	],
	[
		'id' => -3,
		'geometry' => [
			'location' => [
				'lat' => 48.197427,
				'lng' => 16.359181,
			],
		],
		'name' => 'Kuishimbo',
		'website' => 'https://www.facebook.com/pages/Kuishimbo/67343688168',
		'reference' => -1,
	],
	[
		'id' => -4,
		'geometry' => [
			'location' => [
				'lat' => 48.198927,
				'lng' => 16.364725,
			],
		],
		'name' => 'Kojiro - Sushi-Bar',
		'website' => 'https://plus.google.com/106777516565797933298/about',
		'reference' => -1,
	],
	[
		'id' => -5,
		'geometry' => [
			'location' => [
				'lat' => 48.192429,
				'lng' => 16.360060,
			],
		],
		'name' => 'Flying Diner',
		'website' => 'http://www.flying-diner.com/cms/flying-diner-stores/filiale-1050/',
		'reference' => -1,
	],
	[
		'id' => -6,
		'geometry' => [
			'location' => [
				'lat' => 48.195292,
				'lng' => 16.363092,
			],
		],
		'name' => 'Zweitbester',
		'website' => 'http://www.zweitbester.at/',
		'reference' => -1,
	],
	[
		'id' => -7,
		'geometry' => [
			'location' => [
				'lat' => 48.197340,
				'lng' => 16.358725,
			],
		],
		'name' => 'Chinazentrum',
		'website' => 'http://www.chinazentrum-naschmarkt.at/',
		'reference' => -1,
	],
	/*[
		'id' => -8,
		'geometry' => [
			'location' => [
				'lat' => 48.192134,
				'lng' => 16.356006,
			],
		],
		'name' => 'New Point',
		'website' => 'http://www.newpointrestaurant.at/',
		'reference' => -1,
	],*/
	[
		'id' => -9,
		'geometry' => [
			'location' => [
				'lat' => 48.189628,
				'lng' => 16.352214,
			],
		],
		'name' => 'Gondola',
		'website' => 'http://www.gondola.at/',
		'reference' => -1,
	],
	/*[
		'id' => -10,
		'geometry' => [
			'location' => [
				'lat' => 48.191697,
				'lng' => 16.357873,
			],
		],
		'name' => 'Erbsenzählerei',
		'website' => 'http://www.xn--die-erbsenzhlerei-0qb.at/',
		'reference' => -1,
	],*/
	/*[
		'id' => -11,
		'geometry' => [
			'location' => [
				'lat' => 48.192031,
				'lng' => 16.358819,
			],
		],
		'name' => 'Bierometer 2',
		'website' => 'http://www.bierometer-2.at/',
		'reference' => -1,
	],*/
	[
		'id' => -12,
		'geometry' => [
			'location' => [
				'lat' => 48.196901,
				'lng' => 16.365892,
			],
		],
		'name' => 'Nagoya Sushi',
		'website' => 'http://www.nagoyasushi.at/',
		'reference' => -1,
	],
	[
		'id' => -13,
		'geometry' => [
			'location' => [
				'lat' => 48.196341,
				'lng' => 16.357740,
			],
		],
		'name' => 'Karma Ramen',
		'website' => 'http://www.karmaramen.at/',
		'reference' => -1,
	],
	[
		'id' => -14,
		'geometry' => [
			'location' => [
				'lat' => 48.194904,
				'lng' => 16.350911,
			],
		],
		'name' => 'Al Chile!',
		'website' => 'http://www.al-chile.info/',
		'reference' => -1,
	],
	[
		'id' => -15,
		'geometry' => [
			'location' => [
				'lat' => 48.184198,
				'lng' => 16.361702,
			],
		],
		'name' => 'Duspara',
		'website' => 'http://www.duspara.at/',
		'reference' => -1,
	],
	[
		'id' => -16,
		'geometry' => [
			'location' => [
				'lat' => 48.192429,
				'lng' => 16.360060,
			],
		],
		'name' => 'Mosquito Mexican Restaurant',
		'website' => 'http://www.mosquito-mexican-restaurant-wien.at/',
		'reference' => -1,
	],
	[
		'id' => -17,
		'geometry' => [
			'location' => [
				'lat' => 48.189569,
				'lng' => 16.352020,
			],
		],
		'name' => 'Vietnam Bistro',
		'website' => 'https://www.tripadvisor.at/Restaurant_Review-g190454-d2236734-Reviews-Vietnam_Bistro-Vienna.html',
		'reference' => -1,
	],
	[
		'id' => -18,
		'geometry' => [
			'location' => [
				'lat' => 48.189222,
				'lng' => 16.351730,
			],
		],
		'name' => 'ghisallo',
		'website' => 'http://www.ghisallo.cc/',
		'reference' => -1,
	],
];

// searches for venue data in the nearplaces cache
// returns null if nothing found
function nearplace_details_cache_search($name) {
	$data = details_cache_read(null, null);
	foreach ((array)$data as $dataset) {
		foreach ((array)$dataset as $venue) {
			if (isset($venue['name']) && mb_stripos($venue['name'], $name) !== false) {
				return $venue;
			}
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
			if (isset($venue['name']) && mb_stripos($venue['name'], $name) !== false) {
				return $venue;
			}
		}
	}
	return null;
}

/*
 * reads an entry from the details cache
 */
function details_cache_read($id, $reference) {
	// do nothing for custom nearplace venue
	if ($id < 0)
		return null;
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
	// never cache empty data
	if (empty($cache_entry)) {
		return null;
	}
	// do nothing for custom nearplace venue
	if ($id < 0) {
		return null;
	}

	if (!file_exists(DETAILS_CACHE)) {
		$data = [];
	} else {
		$data = file_get_contents(DETAILS_CACHE);
		if (empty($data)) {
			$data = [];
		}
		$data = json_decode($data, true);
		if ($data === null) {
			$data = [];
		}
	}

	$cache_key = [
		'id' => $id,
		'reference' => $reference,
		'timestamp' => time(),
	];
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
	if (!file_exists(NEARPLACES_CACHE)) {
		return null;
	} else {
		$data = file_get_contents(NEARPLACES_CACHE);
		if (empty($data)) {
			return null;
		}
		$data = json_decode($data, true);
		if ($data === null) {
			return null;
		}
	}

	// if parameters are null, return all
	if ($lat === null && $lng === null && $radius === null) {
		return $data;
	}

	// if younger than 1 day, radius +- 100 m and lat/lng distance +- 100 m, return cache entry
	foreach ((array)$data as $cache_key => $cache_entry) {
		$cache_key = explode('|', $cache_key);
		if (
			count($cache_key) == 4 &&
			$cache_key[3] >= strtotime('-1 day') &&
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
	// never cache empty data
	if (empty($cache_entry)) {
		return null;
	}

	if (!file_exists(NEARPLACES_CACHE)) {
		$data = [];
	} else {
		$data = file_get_contents(NEARPLACES_CACHE);
		if (empty($data)) {
			$data = [];
		}
		$data = json_decode($data, true);
		if ($data === null) {
			$data = [];
		}
	}

	$cache_key = [
		'lat' => $lat,
		'lng' => $lng,
		'radius' => $radius,
		'timestamp' => time(),
	];
	$cache_key = implode('|', $cache_key);
	$data[$cache_key] = $cache_entry;
	$data = json_encode($data);
	file_put_contents(NEARPLACES_CACHE, $data);
}

function handle_api_response($api_response) {
	// check status flag
	if (!isset($api_response['status']) || $api_response['status'] != 'OK') {
		return null;
	}

	// save next_page_token in session for future queries
	if (!empty($api_response['next_page_token'])) {
		$_SESSION['nearplaces']['next_page_token'] = trim($api_response['next_page_token']);
	// delete if not found to avoid problems with old session stuff
	} elseif (isset($_SESSION['nearplaces']['next_page_token'])) {
		unset($_SESSION['nearplaces']['next_page_token']);
	}

	return $api_response;
}

function build_response($lat_orig, $lng_orig, $api_response) {
	$response = [];

	foreach ((array)$api_response as $result) {
		// important stuff missing, continue
		if (!isset($result['name']) || !isset($result['geometry']['location'])) {
			continue;
		}

		// clean name from unwanted stuff
		$name = trim(str_replace([ 'Little Stage', 'WerkzeugH', 'Osteria Vinoteca Panarea',
			'Bar Tabacchi', 'Zum Stöger e.U', 'Vinoto', 'La Casa Grande - Cucina Di Tino', 'Victor',
			'Gregors Konditorei', 'Admiral Wettcafé', 'Tanzcafe Jenseits', 'Gergely\'s', 'Ströck',
			'Anker Snack & Coffee Gastronomiebetriebs GmbH', 'Tchibo Filiale', 'Rori\'s Margareten',
			'Restaurant Schwarzer Adler', 'Die Erbsenzählerei', 'Pizza Hotline 5', 'Burger Bring',
			'Schnaps-Bar Club Hofstöckl', 'Margareta', 'Silberwirt', 'Partyservice Schlögl', 'SPAR',
			'LA VITA È BELLA - BAR - ENOTECA', 'Vinothek La Cave', 'broetchen4you.at', 'BILLA AG',
			'Pizza Da Capo', 'Woracziczky Gasthaus', 'Pizza Mann Wien 5', 'Schlacher Event OG',
			'Hofer Wien 5., Margareten', 'Bäckerei Cafe Felzl', 'McDonald\'s', 'Zum alten Fassl',
			'Motto - Restaurant.Bar', 'ristorante GONDOLA', 'FAIRLEIH - Eventausstattung', 'Aromat',
			'Flying Diner 1050', 'Senhor Vinho', 'Café Standard', 'Waldviertlerhof', 'HaasBeisl',
			'SPAR Supermarkt', 'Flying Diner', 'Weinbar - Vinothek Pub Klemo', 'Pizzeria La Carne',
			'Nam Nam Deli', 'Supermarkt', 'Lidl Österreich GmbH', 'Public-theplacetobe', 'Winzig',
			'Mc. Curry', 'Schlupfwinkel Abendbeisl', 'Café Rüdigerhof', 'Restaurant Goldene Glocke',
			'Rudi\'s Beisl', /*'Nam Nam', */'Restaurant Zum Andreas Hofer', 'Eduard Fruth',
			'Wald/4 ler Stub\'n', 'Pro Vino Lang & Partner KG - Vinothek und Weinhandel', 'Entler',
			'TIP TOP TABLE GmbH', 'Cafe Jelinek', 'Pizzeria Tanti Saluti', 'Bangkok Vienna',
			'Café Wortner Kaffeehaus', 'Goldener Spiegel', 'China Restaurant Zhong Xin', 'Rori\'s',
			'Kulinario - Vinzenz Gruppe Service GmbH (Geschäftsbereich Küchen Wien)', 'Wieden Bräu',
			'Taste of India Restaurant', 'Pizzeria Vesuvio da Mario', 'Café Ritter',
			'FILMQUARTIER WIEN - Filmlocations + Requisitenfundus', 'Billa', 'Cafe 60',
			'Österreichische Warenhandels-Aktiengesellschaft', 'Noodles & More', 'Panarea Buffet',
			'MOTTO Catering GmbH', 'Eventmöbel Verleih', 'Hofer', 'PONGANIC Fisch & Greissler',
			'Zur bunten Kuh', 'Schick Hotel Erzherzog Rainer', 'Cafe Drechsler', 'Steirer Stuben',
			'Cafe Willendorf', 'CHILI-WERKSTATT', 'Budapest Bistro', 'B.K Curry Indian Restaurant',
			'Chocolaterie Pâtisserie Fruth', 'Kaffee von Sascha', 'Hotel Ananas', 'Johnny\'s Pub',
		], '', $result['name']), ',.;_.-:"& ');

		// name empty
		if (empty($name)) {
			continue;
		}

		$names[$name] = true;

		$lat = str_replace(',', '.', trim($result['geometry']['location']['lat']));
		$lng = str_replace(',', '.', trim($result['geometry']['location']['lng']));
		$rating = isset($result['rating']) ? $result['rating'] : null;
		$id = isset($result['id']) ? $result['id'] : null;
		$reference = isset($result['reference']) ? $result['reference'] : null;
		$maps_href = htmlspecialchars("https://www.openstreetmap.org/directions?engine=graphhopper_foot&route=$lat_orig,$lng_orig;$lat,$lng");
		//$maps_href = htmlspecialchars("https://maps.google.com/maps?dirflg=r&saddr=$lat_orig,$lng_orig&daddr=$lat,$lng");
		$name_url_safe = urlencode($name);
		$name_escaped = htmlspecialchars($name, ENT_QUOTES);
		$name_escaped = str_replace("'", '', $name_escaped);

		$href = "<a href='javascript:void(0)' onclick='handle_href_reference_details(\"{$id}\", \"{$reference}\", \"{$name_url_safe}\", 0)' title='Homepage'>{$name_escaped}</a>";
		$actions = "<a href='${maps_href}' class='no_decoration lat_lng_link' target='_blank'>
			<div data-enhanced='true' class='ui-link ui-btn ui-icon-location ui-btn-icon-notext ui-btn-inline ui-shadow ui-corner-all' title='OpenStreetMap Route'>OpenStreetMap Route</div>
		</a>";
		if (show_voting()) {
			$actions .= "<div data-enhanced='true' class='ui-link ui-btn ui-icon-plus ui-btn-icon-notext ui-btn-inline ui-shadow ui-corner-all' onclick='vote_up(\"{$name_escaped}\")' title='Vote Up'>Vote up</div>"
					. "<div data-enhanced='true' class='ui-link ui-btn ui-icon-minus ui-btn-icon-notext ui-btn-inline ui-shadow ui-corner-all' onclick='vote_down(\"{$name_escaped}\")' title='Vote Down'>Vote Down</div>";
		}

		$response[] = [
			'name' => $name,
			$href,
			round(distance($lat_orig, $lng_orig, $lat, $lng, false) * 1000),
			//(!$rating) ? '-' : (string)$rating,
			$actions,
		];
	}

	return $response;
}

function remove_doubles($response) {
	$names_found = $response_new = [];

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
	if (!isset($_SESSION['nearplaces']['next_page_token'])) {
		return null;
	}
	$next_page_token = $_SESSION['nearplaces']['next_page_token'];

	// query api
	foreach ($GOOGLE_API_KEYS as $api_key) {
		if (LOG_API_REQUESTS) {
			error_log("@nextpage_search: query api with key $api_key");
		}
		$api_url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?key=$api_key&location=$lat,$lng&radius=$radius&rankby=$rankby&sensor=$sensor&types=restaurant|food&language=de";
		if ($opennow) {
			$api_url .= '&opennow';
		}
		$api_url .= "&pagetoken=$next_page_token";
		$api_response = file_get_contents($api_url);
		if ($api_response === false) {
			continue;
		}
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
		if (LOG_API_REQUESTS) {
			error_log("@nearbysearch: query api with key $api_key");
		}
		$api_url = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?key=$api_key&location=$lat,$lng&radius=$radius&rankby=$rankby&sensor=$sensor&types=restaurant|food&language=de";
		if ($opennow) {
			$api_url .= '&opennow';
		}
		$api_response = file_get_contents($api_url);
		if ($api_response === false) {
			continue;
		}
		$api_response = json_decode($api_response, true);
		break;
	}

	// add custom venues that are in reach
	if (isset($api_response['results'])) {
		foreach ((array)$custom_venues as $venue) {
			$lat_venue = isset($venue['geometry']['location']['lat']) ? $venue['geometry']['location']['lat'] : null;
			$lng_venue = isset($venue['geometry']['location']['lng']) ? $venue['geometry']['location']['lng'] : null;
			if (distance($lat_venue, $lng_venue, $lat, $lng, false) <= $radius) {
				$api_response['results'][] = $venue;
			}
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
			if (LOG_API_REQUESTS) {
				error_log("@details: query api with key $api_key");
			}
			$api_url = "https://maps.googleapis.com/maps/api/place/details/json?key=$api_key&reference=$reference&sensor=$sensor&language=de";
			$api_response = file_get_contents($api_url);
			if ($api_response === false) {
				continue;
			}
			$api_response = json_decode($api_response, true);
			break;
		}
	}

	return handle_api_response($api_response);
}

function nearbysearch_full($lat, $lng, $radius, $sensor) {
	$api_results = nearplace_cache_read($lat, $lng, $radius);
	// we got something, return immediately
	if (!empty($api_results)) {
		return $api_results;
	}

	// make a new query
	$api_results = [];

	// default nearbysearch
	$api_response = nearbysearch($lat, $lng, $radius, $sensor);
	$api_results = array_merge($api_results, (array)$api_response['results']);
	while(1) {
		sleep(2);
		$api_response = nextpage_search($lat, $lng, $radius, $sensor);
		if (!$api_response) {
			break;
		} else {
			$api_results = array_merge($api_results, (array)$api_response['results']);
		}
	}

	// nearby search with opennow
	// UPDATE doesn't really make sense because of caching
	/*$api_response = nearbysearch($lat, $lng, $radius, $sensor, true);
	$api_results = array_merge($api_results, (array)$api_response['results']);
	while(1) {
		sleep(2);
		$api_response = nextpage_search($lat, $lng, $radius, $sensor, true);
		if (!$api_response) {
			break;
		} else {
			$api_results = array_merge($api_results, (array)$api_response['results']);
		}
	}*/

	nearplace_cache_write($lat, $lng, $radius, $api_results);
	return $api_results;
}
