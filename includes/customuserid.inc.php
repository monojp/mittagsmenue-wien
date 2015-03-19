<?php

require_once(__DIR__ . '/includes.php');

// gets the current custom userid set via GET or POST
// null if none
function custom_userid_current() {
	return get_var('userid');
}

// gets the original ip to the current userid
// null if nothing found
function custom_userid_original_ip() {
	$userid = custom_userid_current();

	// no userid means we don't have to look it up
	if (!$userid)
		return null;

	// read db
	$custom_user_data = reset(UserHandler_MySql::getInstance()->get_ip($userid));
	return isset($custom_user_data['ip']) ? $custom_user_data['ip'] : null;
}

// generates a valid access url from the current custom userid
// null if no userid set and url can't be generated
function custom_userid_access_url_get($userid = null) {
	if (!$userid)
		$userid = custom_userid_get();
	if ($userid)
		return trim(SITE_URL, '/') . '/?userid=' . $userid;
	else
		return null;
}

// generates a custom intern userid for the current ip / user,
// saves it to the cache and returns it, on error null
function custom_userid_generate($ip = null) {
	$userid_new = uniqid();

	if (!$ip)
		$ip = get_identifier_ip();

	$custom_userids = array();

	// info mail to admin
	$headers = array();
	$headers[] = "From: " . SITE_FROM_MAIL;
	$headers[] = "MIME-Version: 1.0";
	$headers[] = "Content-type: text/plain; charset=utf-8";
	$headers[] = "X-Mailer: PHP/" . phpversion();
	$headers[] = "Precedence: bulk";
	$message = 'user: ' . ip_anonymize($ip) . "\n" .
		'userid: ' . $userid_new . "\n" .
		'ip: ' . $ip;
	mail(ADMIN_EMAIL, 'custom user id generated', $message, implode("\r\n", $headers));

	$custom_user_data = reset(UserHandler_MySql::getInstance()->get_custom_userid($ip));
	if (empty($custom_user_data))
		UserHandler_MySql::getInstance()->save_custom_userid($ip, $userid_new);
	else
		UserHandler_MySql::getInstance()->update_custom_userid($ip, $userid_new);

	return $userid_new;
}

// gets a custom intern userid for the current ip / user from the cache
// null if none set or cache corrupt ..
function custom_userid_get($ip = null) {
	if (!$ip)
		$ip = get_identifier_ip();

	// read db
	$custom_user_data = reset(UserHandler_MySql::getInstance()->get_custom_userid($ip));
	return isset($custom_user_data['custom_userid']) ? $custom_user_data['custom_userid'] : null;
}
