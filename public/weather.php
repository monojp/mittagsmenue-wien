<?php

	require_once('../includes/weather.inc.php');

	$action = get_var('action');

	// handle actions
	if ($action) {
		// return weather as nice formatted string with timestamp
		if ($action == 'getString')
			echo json_encode(html_compress(getTemperatureString()));
		// undefined action
		else
			echo 'error: undefined action';
	}
	else
		echo 'error: action not set';

?>