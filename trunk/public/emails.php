<?php

	require_once('../includes/includes.php');
	require_once('../includes/vote.inc.php');

	header("Vary: Accept-Encoding");
	header("Content-Type: text/html; charset=UTF-8");

	$action = get_var('action');

	// handle actions
	if ($action) {

		// return email config
		/*if ($action == 'email_config_get') {
			$user = ip_anonymize($_SERVER['REMOTE_ADDR']);
			echo json_encode(email_config_get($user));
		}
		else */if ($action == 'email_config_set') {
			$user = $_SERVER['REMOTE_ADDR'];
			$email = get_var('email');
			$vote_reminder = get_var('vote_reminder');
			$voted_mail_only = get_var('voted_mail_only');

			if ($email != filter_var($email, FILTER_VALIDATE_EMAIL))
				echo json_encode(array('alert' => 'Die Email-Adresse ist ungültig!'));
			else {
				if (email_config_set($user, $email, $vote_reminder, $voted_mail_only) == false)
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
