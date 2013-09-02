<?php

	require_once('../includes/includes.php');

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
		if ($action == 'email_get') {
			$user = ip_anonymize($_SERVER['REMOTE_ADDR']);
			echo json_encode(emails_get($user));
		}
		else if ($action == 'email_set') {
			$user = $_SERVER['REMOTE_ADDR'];
			$email = isset($_GET['email']) ? trim($_GET['email']) : null;
			$email = isset($_POST['email']) ? trim($_POST['email']) : null;

			if ($email != filter_var($email, FILTER_VALIDATE_EMAIL))
				echo json_encode(array('alert' => 'Die Email-Adresse ist ungültig!'));
			else {
				if (email_set($user, $email) == false)
					echo json_encode(array('alert' => 'Fehler beim Speichern der Email-Adresse!'));
				else
					echo json_encode(true);
			}
		}
		// invalid action
		else
			echo json_encode(array('alert' => 'Die übertragene Aktion ist ungültig!'));
	}
	else
		echo json_encode(array('alert' => 'Die übertragene Aktion ist ungültig!'));

?>
