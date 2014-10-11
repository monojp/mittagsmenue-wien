<?php

	require_once('../includes/includes.php');
	require_once('../includes/venues.php');

	session_write_close();

	// --------------
	// handle actions
	$classname = get_var('classname');
	if ($classname !== null) {
		$dateOffset = get_var('dateOffset');
		$timestamp = get_var('timestamp');

		if (class_exists($classname))
			$venue = new $classname;
		else
			exit('Classname invalid');
		echo html_compress($venue->getMenuData());
	}
	else
		exit('No classname set');

?>