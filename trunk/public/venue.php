<?php

	require_once('../includes/includes.php');
	require_once('../includes/venues.php');

	session_write_close();

	header("Vary: Accept-Encoding");
	header("Content-Type: text/html; charset=UTF-8");

	// --------------
	// handle actions
	$classname = get_var('classname');
	if ($classname !== null) {
		$dateOffset = get_var('dateOffset');
		$timestamp = get_var('timestamp');

		$venue = new $classname;
		echo json_encode(html_compress($venue->getMenuData()));
	}
	else {
		echo json_encode(array('alert' => js_message_prepare('No classname set')));
	}

?>