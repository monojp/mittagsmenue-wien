<?php

	require_once('../includes/weather.inc.php');

	header("Vary: Accept-Encoding");
	header("Content-Type: text/html; charset=UTF-8");


	$action = get_var('action');

	// handle actions
	if ($action) {
		// return weather as nice formatted string with timestamp
		if ($action == 'getString')
			echo json_encode(getTemperatureString());
		// undefined action
		else
			echo 'error: undefined action';
	}
	else
		echo 'error: action not set';

?>
