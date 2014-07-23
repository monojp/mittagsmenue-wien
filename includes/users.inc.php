<?php

// get's user config of the user or from all if null
function user_config_get($user = null) {
	if (!file_exists(USERS_FILE))
		return null;

	$data = file_get_contents(USERS_FILE);
	if (empty($data))
		return null;

	$data = json_decode($data, true);
	if (!$data)
		return null;

	if ($user)
		return isset($data[$user]) ? $data[$user] : '';
	else
		return $data;
}

// set's the email of the given user
function user_config_set($ip, $name, $email, $vote_reminder, $voted_mail_only) {
	$all_emails = user_config_get();
	$all_emails = is_array($all_emails) ? $all_emails : array();

	// no mail set => remove entry in config
	if (empty($email))
		unset($all_emails[$ip]);
	else {
		$all_emails[$ip]['name']            = $name;
		$all_emails[$ip]['email']           = $email;
		$all_emails[$ip]['vote_reminder']   = $vote_reminder;
		$all_emails[$ip]['voted_mail_only'] = $voted_mail_only;
	}

	ksort($all_emails);

	$data = json_encode($all_emails, JSON_FORCE_OBJECT);
	return file_put_contents(USERS_FILE, $data);
}

?>