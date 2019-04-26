<?php

/*
 * Global variables
 */
$dateOffset = $timestamp  = 0;
// (new) cachesafe offset via date
$date_GET = $_REQUEST['date'] ?? null;
if ($date_GET) {
	$date = strtotime($date_GET);
	if ($date) {
		$date = new DateTime($date_GET);
		$today = new DateTime(date('Y-m-d'));

		$interval = $today->diff($date);
		$dateOffset = $interval->days;
		if ($interval->invert)
			$dateOffset *= -1;
	}
}
/*else {
	if (date('H') > 17)
	 	$dateOffset = 1;
}*/

// calculate timestamp from offset, use today midnight as base
// to avoid problems with timestamps where the hours are not important but set
$timestamp_base = strtotime(date('Y-m-d') . ' 00:00:00');
//error_log(date('r', $timestamp_base));
if ($dateOffset != 0) {
    $timestamp = strtotime($dateOffset . ' days', $timestamp_base);
} else {
    $timestamp = time();
}

/*
 * Utils
 */
function date_offsetted($params) {
	global $timestamp;

	return date($params, $timestamp);
}
function getGermanDayName($offset = 0) {
	global $timestamp;

	$dayNr = (date('w', $timestamp) + $offset) % 7;
	if ($dayNr == 0)
		return 'Sonntag';
	else if ($dayNr == 1)
		return 'Montag';
	else if ($dayNr == 2)
		return 'Dienstag';
	else if ($dayNr == 3)
		return 'Mittwoch';
	else if ($dayNr == 4)
		return 'Donnerstag';
	else if ($dayNr == 5)
		return 'Freitag';
	else if ($dayNr == 6)
		return 'Samstag';
	else
		return 'not valid';
}
function getGermanMonthName($offset = 0) {
	global $timestamp;
	if ($offset > 0)
		$timestamp_offset = strtotime("+$offset days", $timestamp);
	else if ($offset < 0)
		$timestamp_offset = strtotime("-$offset days", $timestamp);
	else
		$timestamp_offset = $timestamp;
	$monthNr = date('n', $timestamp_offset);
	switch ($monthNr) {
		case 1: return 'Jänner'; break;
		case 2: return 'Februar'; break;
		case 3: return 'März'; break;
		case 4: return 'April'; break;
		case 5: return 'Mai'; break;
		case 6: return 'Juni'; break;
		case 7: return 'Juli'; break;
		case 8: return 'August'; break;
		case 9: return 'September'; break;
		case 10: return 'Oktober'; break;
		case 11: return 'November'; break;
		case 12: return 'Dezember'; break;
		default: return 'not valid'; break;
	}
}
function explode_by_array($delimiter_array, $string, $case_insensitive=true) {
	$delimiter = $delimiter_array[0];

	// extra step to create a uniform value
	if ($case_insensitive)
		$string_uniform = str_ireplace($delimiter_array, $delimiter, $string);
	else
		$string_uniform = str_replace($delimiter_array, $delimiter, $string);

	return explode($delimiter, $string_uniform);
}

/**
 * @deprecated will be unecessary if logic is moved into services which are called by controllers
 * @return string|null
 */
function get_ip(): ?string {
	return $_SERVER['REMOTE_ADDR'] ?? null;
}

function build_url($url_base) {
	global $dateOffset;

	$url_data = [];
	if (isset($_GET['userid'])) {
		$url_data['userid'] = $_GET['userid'];
	}
	if (isset($dateOffset)) {
		$url_data['date'] = \App\Legacy\ContainerHelper::getInstance()->get(\App\Parser\TimeService::class)->dateFromOffset($dateOffset);
	}
	if (isset($_GET['keyword'])) {
		$url_data['keyword'] = $_GET['keyword'];
	}
	if (isset($_GET['food'])) {
		$url_data['food'] = $_GET['food'];
	}
	if (isset($_GET['action'])) {
		$url_data['action'] = $_GET['action'];
	}

	if (strpos($url_base, '?') === false) {
		return $url_base . '?' . http_build_query($url_data, '', '&amp;');
	}

    return $url_base . '&amp;' . http_build_query($url_data, '', '&amp;');
}

