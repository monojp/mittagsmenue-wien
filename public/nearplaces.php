<?php

/*
 * very simple wrapper for the near places search
 */

require_once('../includes/nearplaces.inc.php');

// get variables with fallback to defaults
$action = get_var('action');

// handle actions
if (!$action) {
	echo json_encode([ 'error' => js_message_prepare('Es ist keine Aktion gesetzt!') ]);
	exit;
}

// do a nearbysearch
if ($action == 'nearbysearch') {
	echo json_encode(nearby_search(
		LOCATION_FALLBACK_LAT, LOCATION_FALLBACK_LNG,
		LOCATION_DEFAULT_DISTANCE));
// undefined action
} else {
	echo json_encode(array('error' => js_message_prepare('Die gewählte Aktion ist ungültig!')));
}
