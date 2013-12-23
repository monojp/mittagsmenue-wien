<?php

require_once('../includes/includes.php');

define('NEARPLACES_CACHE', TMP_PATH . 'nearplaces_cache.json');
define('DETAILS_CACHE', TMP_PATH . 'details_cache.json');
define('LOG_API_REQUESTS', true);

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
			'silberwirt', 'margareta', 'altes fassl', 'aus:klang', 'delicious monster', 'haas beisl', 'kunsthallencafé', 'taste of india',
			'zum schwarzer adler', 'schlossquadrat', 'Nam Nam', 'Waldviertlerhof',
			'Schloss Schönbrunn Konzerte', 'betriebs . nfg. keg', 'ges.m.b.h', 'ges.mbh', 'gesmbh', 'gmbh', 'm.b.h', 'immobilien', 'e.u', 'weinbar', 'gesellschaft',
			'gasthaus', 'brauerei', 'Betriebs . Nfg. KEG', 'Co KG', 'betriebs', 'Billa AG', 'Billa Aktien', 'Brabenetz & Dirnwöber',
			'm. islam kg', 'andreas gruber', 'Schnaps- Club Hofstöckl', 'Wien - Margit Köffler', 'Göd Manfred', 'keg', 'hans rudolf siegl',
			'fairleih', 'eventausstattung', 'tchibo / eduscho filiale', 'Billa', 'Hofer', 'Penny Markt',
		), '', $result['name']), ',.;_.-:"& ');
		$name_clean_check = trim(str_ireplace(array(
			'restaurant', 'ristorante'
		), '', $name));

		// name empty
		if (empty($name) || empty($name_clean_check))
			continue;

		$names[$name] = true;

		$lat  = str_replace(',', '.', trim($result['geometry']['location']['lat']));
		$lng  = str_replace(',', '.', trim($result['geometry']['location']['lng']));
		$rating = isset($result['rating']) ? $result['rating'] : null;
		$id = isset($result['id']) ? $result['id'] : null;
		$reference = isset($result['reference']) ? $result['reference'] : null;
		$types = isset($result['types']) ? $result['types'] : null;
		$price_level = isset($result['price_level']) ? $result['price_level'] : null;
		$maps_href = "https://maps.google.com/maps?dirflg=r&saddr=$lat_orig,$lng_orig&daddr=$lat,$lng";
		$name_url_safe = urlencode($name);
		$href = "<a href='javascript:void(0)' onclick='handle_href_reference_details(\"$id\", \"$reference\", \"$name_url_safe\")' title='Homepage' target='_blank'>$name</a>";

		$response[] = array(
			'name'        => $name,
			'lat'         => $lat,
			'lng'         => $lng,
			'href'        => $href,
			'maps_href'   => $maps_href,
			'rating'      => $rating,
			'id'          => $id,
			'reference'   => $reference,
			'types'       => $types,
			'price_level' => $price_level,
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

	return $response_new;
}

function nextpage_search($lat, $lng, $radius, $sensor, $opennow=false, $rankby='prominence') {
	global $GOOGLE_API_KEYS;

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

function nearbysearch($lat, $lng, $radius, $sensor, $opennow=false, $rankby='prominence') {
	global $GOOGLE_API_KEYS;

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

	return handle_api_response($api_response);
}

function details($id, $reference, $sensor) {
	global $GOOGLE_API_KEYS;

	// query api
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

	return handle_api_response($api_response);
}

?>