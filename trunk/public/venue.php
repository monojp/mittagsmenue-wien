<?php

	require_once('../includes/includes.php');
	require_once('../includes/venues.php');

	session_write_close();

	header("Content-Type: application/json; charset=UTF-8");

	// --------------
	// handle actions
	$classname = get_var('classname');
	if ($classname !== null) {
		$dateOffset = get_var('dateOffset');
		$timestamp = get_var('timestamp');

		if (class_exists($classname))
			$venue = new $classname;
		else
			echo json_encode(array('alert' => js_message_prepare('Classname invalid')));
		echo json_encode(html_compress($venue->getMenuData()));
	}
	else
		echo json_encode(array('alert' => js_message_prepare('No classname set')));

?>
