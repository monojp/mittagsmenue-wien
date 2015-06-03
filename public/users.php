<?php

require_once('../includes/includes.php');
require_once('../includes/vote.inc.php');

$action = get_var('action');

// handle actions
if (!$action)
	exit(json_encode(array('alert' => js_message_prepare('Die übertragene Aktion ist ungültig!'))));

if ($action == 'user_config_set') {
	$ip              = get_identifier_ip();
	$name            = get_var('name');
	$email           = get_var('email');
	$vote_reminder   = (get_var('vote_reminder') == 'true');
	$voted_mail_only = (get_var('voted_mail_only') == 'true');

	if ($email != filter_var($email, FILTER_VALIDATE_EMAIL))
		echo json_encode(array('alert' => js_message_prepare('Die Email-Adresse ist ungültig!')));
	else {
		if (empty(UserHandler_MySql::getInstance()->get($ip)))
			UserHandler_MySql::getInstance()->save($ip, $name, $email, $vote_reminder, $voted_mail_only, null /*TODO*/);
		else
			UserHandler_MySql::getInstance()->update($ip, $name, $email, $vote_reminder, $voted_mail_only, null /*TODO*/);
		echo json_encode(array('alert' => js_message_prepare('Daten wurden gespeichert.')));
	}
}
// invalid action
else
	echo json_encode(array('alert' => js_message_prepare('Die übertragene Aktion ist ungültig!')));
