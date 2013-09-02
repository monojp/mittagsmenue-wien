<?php

	require_once('../includes/weather.inc.php');

	header("Vary: Accept-Encoding");
	header("Content-Type: text/html; charset=UTF-8");


	$action = null;
	if (isset($_POST['action']))
		$action = trim($_POST['action']);
	else if (isset($_GET['action']))
		$action = trim($_GET['action']);

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
