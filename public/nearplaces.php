<?php

/*
 * very simple wrapper for the google near places search
 */

require_once('../includes/nearplaces.inc.php');

header("Vary: Accept-Encoding");
header("Content-Type: text/html; charset=UTF-8");

// get variables with fallback to defaults
$action = get_var('action');
$lat = is_var('lat') ? get_var('lat') : LOCATION_FALLBACK_LAT;
$lng = is_var('lng') ? get_var('lng') : LOCATION_FALLBACK_LNG;
$radius = is_var('radius') ? get_var('radius') : '100';
$sensor = is_var('sensor') ? get_var('sensor') : 'false';
$id = is_var('id') ? get_var('id') : null;
$reference = is_var('reference') ? get_var('reference') : null;

// handle actions
if ($action) {
	// get the next page of a nearbysearch
	if ($action == 'nextpage') {
		$api_response = nextpage_search($lat, $lng, $radius, $sensor);
		$response = build_response($lat, $lng, $api_response['results']);
		echo json_encode(remove_doubles($response));
	}
	// do a new nearbysearch for restaurants
	else if ($action == 'nearbysearch') {
		$api_response = nearbysearch($lat, $lng, $radius, $sensor);
		$response = build_response($lat, $lng, $api_response['results']);
		echo json_encode(remove_doubles($response));
	}
	// do a places details search with a given place reference
	else if ($action == 'details') {
		if (!$id || !$reference)
			echo json_encode(array('error' => '"reference" oder "id" leer!'));

		$response = details_cache_read($id, $reference);
		if ($response === null) {
			$response = details($id, $reference, $sensor);
			details_cache_write($id, $reference, $response);
		}
		echo json_encode($response);
	}
	// do a full search with all pages (takes rather long because of sleeps)
	// therefore it is cached
	else if ($action == 'nearbysearch_full') {
		$api_results = nearplace_cache_read($lat, $lng, $radius);
		if ($api_results === null) {
			$api_results = array();

			// default nearbysearch
			$api_response = nearbysearch($lat, $lng, $radius, $sensor);
			$api_results = array_merge($api_results, (array)$api_response['results']);
			while(1) {
				usleep(1800 * 1000);
				$api_response = nextpage_search($lat, $lng, $radius, $sensor);
				if (!$api_response)
					break;
				else
					$api_results = array_merge($api_results, (array)$api_response['results']);
			}

			// nearby search with opennow
			$api_response = nearbysearch($lat, $lng, $radius, $sensor, true);
			$api_results = array_merge($api_results, (array)$api_response['results']);
			while(1) {
				usleep(1800 * 1000);
				$api_response = nextpage_search($lat, $lng, $radius, $sensor, true);
				if (!$api_response)
					break;
				else
					$api_results = array_merge($api_results, (array)$api_response['results']);
			}

			if ($api_results !== null)
				nearplace_cache_write($lat, $lng, $radius, $api_results);
		}

		// build final response
		$response = build_response($lat, $lng, $api_results);
		echo json_encode(remove_doubles($response));
	}
	// undefined action
	else
		echo json_encode(array('error' => 'Die gewählte Aktion ist ungültig!'));
}
else
	echo json_encode(array('error' => 'Es ist keine Aktion gesetzt!'));

?>
