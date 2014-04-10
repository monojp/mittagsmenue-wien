<?php

	require_once('../includes/includes.php');
	require_once('../includes/vote.inc.php');

	$action = get_var('action');

	// handle actions
	if ($action) {
		if ($action == 'email_config_set') {
			$user = get_identifier_ip();
			$email = get_var('email');
			$vote_reminder = get_var('vote_reminder');
			$voted_mail_only = get_var('voted_mail_only');

			if ($email != filter_var($email, FILTER_VALIDATE_EMAIL))
				echo json_encode(array('alert' => js_message_prepare('Die Email-Adresse ist ungültig!')));
			else {
				if (email_config_set($user, $email, $vote_reminder, $voted_mail_only) == false)
					echo json_encode(array('alert' => js_message_prepare('Fehler beim Speichern der Email-Adresse!')));
				else
					echo json_encode(true);
			}
		}
		// invalid action
		else
			echo json_encode(array('alert' => js_message_prepare('Die übertragene Aktion ist ungültig!')));
	}
	else
		echo json_encode(array('alert' => js_message_prepare('Die übertragene Aktion ist ungültig!')));

?>