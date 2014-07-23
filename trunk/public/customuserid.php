<?php

	require_once('../includes/customuserid.inc.php');

	header("Vary: Accept-Encoding");
	header("Content-Type: text/html; charset=UTF-8");

	// disallow access for extern ips; even with valid userid
	if (!is_intern_ip()) {
		echo json_encode(array('alert' => 'Zugriff verweigert!'));
		exit;
	}

	$action = get_var('action');

	// handle actions
	if ($action) {
		// generate new userid and return the corresponding url on success
		if ($action == 'custom_userid_generate') {
			if (custom_userid_generate())
				echo json_encode(array('access_url' => custom_userid_access_url_get()));
			else
				echo json_encode(array('alert' => 'Fehler beim Erstellen der externen Zugriffs-URL.'));
		}
		// undefined action
		else
			echo 'error: undefined action';
	}
	else
		echo 'error: action not set';


?>