<?php

$DB_CONFIGS = [
	// list of db connections (multiple are possible for fallback reasons)
	[
		'DB_SERVER' => '<server>',
		'DB_NAME' => '<database>',
		'DB_USER' => '<username>',
		'DB_PASSWORD' => '<password>',
	],
];

define('TMP_PATH', dirname(__FILE__) . '/../tmp/');
define('ALLOW_VOTING_IP_PREFIX', '192.168.0.');

define('LOCATION_FALLBACK', 'Bräuhausgasse 4, 1050 Wien');
define('LOCATION_FALLBACK_LAT', 48.190341);
define('LOCATION_FALLBACK_LNG', 16.356591);
define('LOCATION_DEFAULT_DISTANCE', 5000);

$GOOGLE_API_KEYS = [
	/* put a list of api keys here (1 is also enough) */
];

$fb_app_id = '<your fb app id>';
$fb_app_secret = '<your fb app secret>';

define('USE_MINIMZED_JS_CSS', true);
define('USE_SEMAPHORES', true);
define('CACHE_SPECIAL_DATA', true);
define('PERSIST_MENU_DATA', true);

define('CONTENT_CUTOFF_LENGTH', 280);

define('SITE_TITLE', 'Mittagsmenü Wien');
define('SITE_URL', '<url>');
define('SITE_FROM_MAIL', 'Your Name <<email>>');
define('META_KEYWORDS', SITE_TITLE);
define('META_DESCRIPTION', SITE_TITLE);
define('CONTACT_HREF', '<url>');
define('IMPRESSUM_HREF', '<url>');
define('PRIVACY_INFO', '<text>');

define('SEARCH_PROVIDER', 'https://searx.info/?language=de-AT&q=');

$locales = [ 'de_AT.UTF-8', 'de_DE.UTF-8' ];
$locale_new = setlocale(LC_ALL, $locales);
if (!in_array($locale_new, $locales)) {
	trigger_error("could not set target locale. current: '$locale_new'", E_USER_WARNING);
}

date_default_timezone_set('Europe/Vienna');

$voting_show_start = strtotime('08:00');
$voting_show_end = strtotime('15:00');

if (date('N') == 5) {
	$voting_over_time = strtotime('14:00');
} else {
	$voting_over_time = strtotime('11:50');
}
