<?php

	require_once('../includes/includes.php');

	// handle actions
	if (isset($_POST['action'])) {
		$action = trim($_POST['action']);

		// lat/lng to address
		if ($action == 'latlngToAddress') {
			$lat = trim($_POST['lat']);
			$lng = trim($_POST['lng']);

			echo json_encode(
				array(
					'address' => latlngToAddress($lat, $lng)
				)
			);
		}
		// address to lat/lng
		else if ($action == 'addressToLatLong') {
			$address = trim($_POST['address']);

			echo json_encode(addressToLatLong($address));
		}
		// distance between two lat/lng
		else if ($action == 'distance') {
			$lat1 = trim($_POST['lat1']);
			$lng1 = trim($_POST['lng1']);
			$lat2 = trim($_POST['lat1']);
			$lng2 = trim($_POST['lng2']);

			echo json_encode(
				array(
					'distance' => distance($lat1, $lng1, $lat2, $lng2, false)
				)
			);
		}
		// undefined action
		else {
			echo 'error: undefined action';
		}
	}
	else {
		echo 'error: action not set';
	}

?>