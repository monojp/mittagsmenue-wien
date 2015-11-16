<?php

require_once(__DIR__ . '/includes.php');

// gets the current custom userid set via GET or POST
// null if none
function custom_userid_current() {
	return get_var('userid');
}

// generates a valid access url from the current custom userid
// null if no userid set
function custom_userid_access_url_get($userid) {
	if ($userid) {
		return trim(SITE_URL, '/') . '/?userid=' . $userid;
	} else {
		return null;
	}
}

// generates a custom intern userid for the current ip / user,
// saves it to the cache and returns it, on error null
function custom_userid_generate($ip = null) {
	if (!$ip) {
		$ip = get_identifier_ip();
	}

	// update user data and return new userid
	UserHandler_MySql::getInstance()->update_custom_userid($ip, ( $userid_new = uniqid() ));
	return $userid_new;
}
