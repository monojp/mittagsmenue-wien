<?php

require_once(__DIR__ . '/includes.php');

define('CUSTOM_USERID_CACHE_FILE', TMP_PATH . 'custom_userid_cache.json');

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

	// read cache
	if (file_exists(CUSTOM_USERID_CACHE_FILE)) {
		$custom_userids = json_decode(file_get_contents(CUSTOM_USERID_CACHE_FILE), true);
		// non corrupt cache
		if ($custom_userids) {
			foreach ((array)$custom_userids as $ip => $custom_userid) {
				if ($userid == $custom_userid)
					return $ip;
			}
		}
	}

	return null;
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

	// read cache
	if (file_exists(CUSTOM_USERID_CACHE_FILE)) {
		$custom_userids = json_decode(file_get_contents(CUSTOM_USERID_CACHE_FILE), true);
		// corrupt cache
		if (!$custom_userids)
			$custom_userids = array();
	}
	// set url
	$custom_userids[$ip] = $userid_new;
	ksort($custom_userids);
	// write to cache
	if (file_put_contents(CUSTOM_USERID_CACHE_FILE, json_encode($custom_userids)) !== FALSE)
		return $userid_new;
	else
		return null;
}

// gets a custom intern userid for the current ip / user from the cache
// null if none set or cache corrupt ..
function custom_userid_get($ip = null) {
	if (!$ip)
		$ip = get_identifier_ip();

	// read cache
	if (file_exists(CUSTOM_USERID_CACHE_FILE)) {
		$custom_userids = json_decode(file_get_contents(CUSTOM_USERID_CACHE_FILE), true);
		// non corrupt cache and entry exists
		if (isset($custom_userids[$ip]))
			return $custom_userids[$ip];
	}

	return null;
}
