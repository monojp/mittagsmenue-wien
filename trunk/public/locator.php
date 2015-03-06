<?php

require_once('../includes/includes.php');

// handle actions
$action = get_var('action');
if (!$action)
	exit('error: action not set');

// lat/lng to address
if ($action == 'latlngToAddress') {
	$lat = get_var('lat');
	$lng = get_var('lng');

	echo json_encode(
		array(
			'address' => latlngToAddress($lat, $lng)
		)
	);
}
// address to lat/lng
else if ($action == 'addressToLatLong') {
	$address = get_var('address');

	echo json_encode(addressToLatLong($address));
}
// distance between two lat/lng
else if ($action == 'distance') {
	$lat1 = get_var('lat1');
	$lng1 = get_var('lng1');
	$lat2 = get_var('lat1');
	$lng2 = get_var('lng2');

	echo json_encode(
		array(
			'distance' => distance($lat1, $lng1, $lat2, $lng2, false)
		)
	);
}
// undefined action
else
	echo 'error: undefined action';
