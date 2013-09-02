<?php

	require_once('../includes/includes.php');
	require_once('../includes/venues.php');

	header("Vary: Accept-Encoding");
	header("Content-Type: text/html; charset=UTF-8");

	// --------------
	// handle actions
	if (isset($_POST['classname'])) {
		$classname = trim($_POST['classname']);
		$dateOffset = trim($_POST['dateOffset']);
		$timestamp = trim($_POST['timestamp']);

		$venue = new $classname;
		echo json_encode($venue->getMenuData());
	}
	else {
		echo json_encode(array('alert' => 'No classname set'));
	}

?>
