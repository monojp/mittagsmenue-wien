<?php

/*
 * very simple wrapper for the near places search
 */

require_once('../includes/nearplaces.inc.php');

// get variables with fallback to defaults
$action = get_var('action');
$lat = is_var('lat') ? get_var('lat') : LOCATION_FALLBACK_LAT;
$lng = is_var('lng') ? get_var('lng') : LOCATION_FALLBACK_LNG;
$radius = is_var('radius') ? get_var('radius') : '100';

// handle actions
if (!$action) {
	echo json_encode([ 'error' => js_message_prepare('Es ist keine Aktion gesetzt!') ]);
	exit;
}

// do a nearbysearch
if ($action == 'nearbysearch') {
	echo json_encode(nearby_search($lat, $lng, $radius));
// undefined action
} else {
	echo json_encode(array('error' => js_message_prepare('Die gewählte Aktion ist ungültig!')));
}
