<?php

$DB_CONFIGS = array(
	// live db
	array(
		'DB_SERVER'   => '<server>',
		'DB_NAME'     => '<database>',
		'DB_USER'     => '<username>',
		'DB_PASSWORD' => '<password>',
	),
	// test db as backup
	array(
		'DB_SERVER'   => '<server>',
		'DB_NAME'     => '<database>',
		'DB_USER'     => '<username>',
		'DB_PASSWORD' => '<password>',
	),
);

define('TMP_PATH', dirname(__FILE__) . '/../tmp/');
define('ALLOW_VOTING_IP_PREFIX', '192.168.0.');
define('USE_SSL', true);

define('LOCATION_FALLBACK', 'Bräuhausgasse 3-5, 1050 Wien');
define('LOCATION_FALLBACK_LAT', 48.189730);
define('LOCATION_FALLBACK_LNG', 16.356638);
define('LOCATION_DEFAULT_DISTANCE', 5000);

$GOOGLE_API_KEYS = array(
	/* put a list of api keys here (1 is also enough) */
);

$fb_app_id     = '<your fb app id>';
$fb_app_secret = '<your fb app secret>';

define('USE_MINIMZED_JS_CSS', true);

define('SITE_TITLE', 'Mittagsmenü Wien');
define('SITE_URL', '<url>');
define('SITE_FROM_MAIL', 'Your Name <<email>>');
define('META_KEYWORDS', SITE_TITLE);
define('META_DESCRIPTION', SITE_TITLE);
define('CONTACT_HREF', '<url>');
define('IMPRESSUM_HREF', '<url>');
define('PRIVACY_INFO', '<text>');

define('ADMIN_EMAIL', '<email>');

$locales = array('de_AT.UTF-8', 'de_DE.UTF-8');
$locale_new = setlocale(LC_ALL, $locales);
if (!in_array($locale_new, $locales))
	trigger_error("could not set target locale. current: '$locale_new'", E_USER_WARNING);

date_default_timezone_set('Europe/Vienna');

$voting_show_start = strtotime('08:00');
$voting_show_end = strtotime('15:00');

if (date('N') == 5)
	$voting_over_time = strtotime('14:00');
else
	$voting_over_time = strtotime('11:50');

$changelog = array(
	//strtotime('1970-01-01') => 'New features: x & y',
);

$tracking_code = "";
