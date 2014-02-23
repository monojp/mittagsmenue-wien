<?php

require_once('config.php');
require_once('textfixes.php');
require_once('CacheHandler_MySql.php');
require_once('customuserid.inc.php');

// redirect bots to minimal site without ajax content
// should be done even before starting a session and with a 301 http code
if (
	!isset($_GET['minimal']) &&
	isset($_SERVER['HTTP_USER_AGENT']) && stringsExist(strtolower($_SERVER['HTTP_USER_AGENT']), array('bot', 'google', 'spider', 'yahoo', 'search', 'crawl'))
) {
	error_log('bot "' . $_SERVER['HTTP_USER_AGENT'] . '" redirect to minimal site, query: ' . $_SERVER['QUERY_STRING']);
	header('HTTP/1.1 301 Moved Permanently');
	header('Location: ?minimal&' . $_SERVER['QUERY_STRING']);
}

mb_internal_encoding('UTF-8');

// valid session for 3 hours
$server_name = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '';
session_set_cookie_params(3 * 60, '/', $server_name, false, true);
session_start();

/*
 * Global variables
 */
$dateOffset = $timestamp = $date_GET = 0;
// (new) cachesafe offset via date
if (isset($_GET['date']) && is_string($_GET['date'])) {
	$date_GET = trim($_GET['date']);
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

// calculate timestamp from offset
if ($dateOffset != 0)
	$timestamp = strtotime($dateOffset . ' days');
else
	$timestamp = time();

/*
 * Utils
 */
function cacheSafeUrl($file) {
	return $file . "?" . filemtime($file);
}
function strposAfter($haystack, $needle, $offset=0) {
	$pos = strpos($haystack, $needle, $offset);
	if ($pos !== FALSE)
		$pos += strlen($needle);
	return $pos;
}
/*
 * finds the n'th occurence of a string
 */
function strnpos($haystack, $needle, $offset, $n) {
	$pos = $offset;
	for ($i=0; $i<$n; $i++)
		$pos = strpos($haystack, $needle, $pos + strlen($needle));
	return $pos;
}
function strnposAfter($haystack, $needle, $offset, $n) {
	$pos = strnpos($haystack, $needle, $offset, $n);
	if ($pos !== FALSE)
		$pos += strlen($needle);
	return $pos;
}
function striposAfter($haystack, $needle, $offset=0) {
	$pos = stripos($haystack, $needle, $offset);
	if ($pos !== FALSE)
		$pos += strlen($needle);
	return $pos;
}
function mb_str_replace($needle, $replacement, $haystack) {
    $needle_len = mb_strlen($needle);
    $replacement_len = mb_strlen($replacement);
    $pos = mb_strpos($haystack, $needle);
    while ($pos !== false)
    {
        $haystack = mb_substr($haystack, 0, $pos) . $replacement
                . mb_substr($haystack, $pos + $needle_len);
        $pos = mb_strpos($haystack, $needle, $pos + $replacement_len);
    }
    return $haystack;
}
function str_replace_array($search, $replace, $subject) {
	foreach ($search as $s)
		$subject = str_replace($s, $replace, $subject);
	return $subject;
}

function str_replace_wrapper($searchReplace, $subject) {
	foreach ($searchReplace as $search => $replace) {
		$subject = str_replace($search, $replace, $subject);
	}
	return $subject;
}
function date_offsetted($params) {
	global $timestamp;

	return date($params, $timestamp);
}
function shuffle_assoc(&$array) {
	$keys = array_keys($array);

	shuffle($keys);

	$new = array();
	foreach($keys as $key) {
		$new[$key] = $array[$key];
	}

	$array = $new;
	return true;
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
function getGermanDayNameShort($offset = 0) {
	return substr(getGermanDayName($offset), 0, 2);
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
function cleanText($text) {
	global $searchReplace;

	$text = html_entity_decode($text, ENT_COMPAT/* | ENT_HTML401*/, 'UTF-8');
	$text = str_replace_array(array('`', '´'), '', $text);
	$text = str_replace_wrapper($searchReplace, $text);
	$text = trim($text);

	return $text;
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
function stringsExist($haystack, $needles) {
	$exists = false;
	foreach ($needles as $needle) {
		if (strpos($haystack, $needle) !== false) {
			$exists = true;
			break;
		}
	}
	return $exists;
}
function array_occurence_count($needle, $haystack) {
	$counter = 0;
	foreach ($haystack as $n) {
		if (strcmp($n, $needle) == 0)
			$counter++;
	}
	return $counter;
}
function get_identifier_ip() {
	$ip = custom_userid_original_ip();
	if (!$ip && isset($_SERVER['REMOTE_ADDR']))
		$ip = $_SERVER['REMOTE_ADDR'];
	return $ip;
}
function is_intern_ip() {
	//return true; // DEBUG
	$ip = get_identifier_ip();
	$allow_voting_ip_prefix = ALLOW_VOTING_IP_PREFIX;
	if (empty($allow_voting_ip_prefix) || strpos($ip, $allow_voting_ip_prefix) === 0)
		return true;
	return false;
}
function show_voting() {
	//return true; // DEBUG
	global $dateOffset;
	global $voting_show_start;
	global $voting_show_end;

	return (
		is_intern_ip() &&
		$dateOffset == 0 &&
		time() >= $voting_show_start &&
		time() <= $voting_show_end
	);
}
function show_weather() {
	//return true; // DEBUG
	global $dateOffset;

	return ($dateOffset == 0);
}

/* returns an array with all the foods, the dates
 * and the datasetSize (amount of cache files)
 */
function getCacheData($keyword, $foodKeyword) {
	global $explodeNewLines;
	global $cacheDataExplode;
	global $cacheDataIgnore;
	global $cacheDataDelete;

	if (empty($keyword) || empty($foodKeyword))
		return null;
	if (strlen($keyword) < 3 || strlen($foodKeyword) < 3)
		return null;

	// sort cacheDataExplode (longer stuff first)
	usort($cacheDataExplode, function($a,$b) {
		return strlen($b) - strlen($a);
	});

	$foods = array(); // ingredients
	$dates = array(); // dates when food was served
	$compositions = array(); // which thing the food was part of
	$compositionsAbsolute = array(); // list of compositions without ingredience association
	$datasetSize = 0;

	$cacheHandler = new CacheHandler_MySql();
	$result = $cacheHandler->queryCache($keyword, $foodKeyword, array('timestamp', 'dataSource', 'data'));

	foreach ($result as $row) {
		$food = $row['data'];

		// stats-cleaner
		if (stringsExist($food, $cacheDataDelete) !== false) {
			$cacheHandler->deleteFromCache($row['timestamp'], $row['dataSource']);
		}

		// food cleaner (for old stat files)
		$food = cleanText($food);

		if (!empty($dataKeyword) && stripos($food, $dataKeyword) === false)
			continue;

		// multi food support (e.g. 1. pizza with ham, 2. pizza with bacon, ..)
		$foodMulti = explode_by_array($cacheDataExplode, $food);
		if (count($foodMulti) > 1) {
			foreach ($foodMulti as $foodSingle) {
				$foodSingle = str_ireplace($cacheDataIgnore, '', $foodSingle);
				$foodSingle = trim($foodSingle);

				if (!empty($foodKeyword) && stripos($foodSingle, $foodKeyword) === false)
					continue;

				if (empty($foodSingle))
					continue;

				if (!isset($foods[$foodSingle]))
					$foods[$foodSingle] = 1;
				else
					$foods[$foodSingle] += 1;

				$foodOrig = array();
				$foodMultiOrig = array_unique(explode_by_array($explodeNewLines, $food));
				foreach ($foodMultiOrig as $f) {
					$f = str_ireplace($cacheDataIgnore, '', $f);
					$f = cleanText($f);
					$f_ingredients = explode_by_array($cacheDataExplode, $f);

					// clean ingredients
					foreach ($f_ingredients as &$ingredient)
						$ingredient = cleanText($ingredient);
					unset($ingredient);

					if (in_array($foodSingle, $f_ingredients) && !empty($f))
						$foodOrig[] = $f;
				}
				$foodOrig = implode('<br />', array_unique($foodOrig));

				if (!isset($dates[$foodSingle]) || !in_array($row['timestamp'], $dates[$foodSingle])) {
					$compositions[$foodSingle][$foodOrig] = '';
					$dates[$foodSingle][] = $row['timestamp'];
				}

				// composition absolute counter without ingredient association
				if (isset($compositionsAbsolute[$foodOrig])) {
					if (!in_array($row['timestamp'], $compositionsAbsolute[$foodOrig]['dates'])) {
						$compositionsAbsolute[$foodOrig]['cnt'] += 1;
						$compositionsAbsolute[$foodOrig]['dates'][] = $row['timestamp'];
					}
				}
				else {
					$compositionsAbsolute[$foodOrig]['cnt'] = 1;
					$compositionsAbsolute[$foodOrig]['dates'][] = $row['timestamp'];
				}
			}
		}
		// normal food support (e.g. pizza with mushrooms)
		else {
			if (!isset($foods[$food]))
				$foods[$food] = 1;
			else
				$foods[$food] += 1;

			if (!isset($dates[$food]) || !in_array($row['timestamp'], $dates[$food])) {
				$compositions[$food][] = $food;
				$dates[$food][] = $row['timestamp'];
			}

			// composition absolute counter without ingredient association
			if (isset($compositionsAbsolute[$food])) {
				if (!in_array($row['timestamp'], $compositionsAbsolute[$food]['dates'])) {
					$compositionsAbsolute[$food]['cnt'] += 1;
					$compositionsAbsolute[$food]['dates'][] = $row['timestamp'];
				}
			}
			else {
				$compositionsAbsolute[$food]['cnt'] = 1;
				$compositionsAbsolute[$food]['dates'][] = $row['timestamp'];
			}
		}

		// increase datasetSize counter
		$datasetSize++;
	}

	// sort food counting arrays
	arsort($foods);
	arsort($compositionsAbsolute);

	return array('foods' => $foods, 'dates' => $dates, 'compositions' => $compositions, 'compositionsAbsolute' => $compositionsAbsolute, 'datasetSize' => $datasetSize);
}

function pdftohtml($file) {
	$fileUniq = $file . uniqid();

	// read data to uniqe tmp file
	$tmpPath = tempnam('/tmp', 'food_pdf_');
	$data = @file_get_contents($file);

	// abort if pdf data empty / invalid
	if (empty($data))
		return null;

	file_put_contents($tmpPath, $data);

	// convert to hmtl
	// single HTML with all pages, ignore images, no paragraph merge, no frames, force hidden text extract
	shell_exec("pdftohtml -s -i -nomerge -noframes -hidden $tmpPath $tmpPath");

	// return html
	return file_get_contents($tmpPath . '.html');
}

function doctotxt($file) {
	$fileUniq = $file . uniqid();

	// read data to uniqe tmp file
	$tmpPath = tempnam('/tmp', 'food_txt_');
	$data = @file_get_contents($file);

	// abort if pdf data empty / invalid
	if (empty($data))
		return null;

	file_put_contents($tmpPath, $data);

	// convert to txt
	$txt = shell_exec("antiword -w 99999 -s $tmpPath 2>&1");

	// return utf-8 txt
	return mb_check_encoding($txt, 'UTF-8') ? $txt : utf8_encode($txt);
}

// api see https://developers.google.com/maps/documentation/geocoding
function addressToLatLong($address) {
	$address = urlencode(trim($address));
	$api_url = "https://maps.googleapis.com/maps/api/geocode/json?address=$address&sensor=false";
	$data = file_get_contents($api_url);
	$data = json_decode($data);
	if ($data->status == 'OK') {
		return array(
			'lat' => str_replace(',', '.', trim($data->results[0]->geometry->location->lat)),
			'lng' => str_replace(',', '.', trim($data->results[0]->geometry->location->lng))
		);
	}
	return null;
}
function latlngToAddress($lat, $lng) {
	$lat = trim(str_replace(',', '.', $lat));
	$lng = trim(str_replace(',', '.', $lng));
	$latlng = urlencode("$lat,$lng");
	$api_url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=$latlng&sensor=false";
	$data = file_get_contents($api_url);
	$data = json_decode($data, true);
	if ($data['status'] == 'OK') {
		if (empty($data['results']))
			return null;
		return trim($data['results'][0]['formatted_address']);
	}
	return null;
}
function latlngToPostalCode($lat, $lng) {
	$lat = trim(str_replace(',', '.', $lat));
	$lng = trim(str_replace(',', '.', $lng));
	$latlng = urlencode("$lat,$lng");
	$api_url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=$latlng&sensor=false";
	$data = file_get_contents($api_url);
	$data = json_decode($data, true);
	if ($data['status'] == 'OK') {
		if (empty($data['results']))
			return null;
		foreach ($data['results'][0]['address_components'] as $result) {
			if (count($result['types']) == 1 && reset($result['types']) == 'postal_code')
				return trim($result['short_name']);
		}
	}
	return null;
}
function distance($lat1, $lng1, $lat2, $lng2, $miles = true) {
	$lat1 = floatval($lat1);
	$lng1 = floatval($lng1);
	$lat2 = floatval($lat2);
	$lng2 = floatval($lng2);
	$pi80 = M_PI / 180;
	$lat1 *= $pi80;
	$lng1 *= $pi80;
	$lat2 *= $pi80;
	$lng2 *= $pi80;

	$r = 6372.797; // mean radius of Earth in km
	$dlat = $lat2 - $lat1;
	$dlng = $lng2 - $lng1;
	$a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlng / 2) * sin($dlng / 2);
	$c = 2 * atan2(sqrt($a), sqrt(1 - $a));
	$km = $r * $c;

	return ($miles ? ($km * 0.621371192) : $km);
}

function format_date($data, $format) {
	if (is_array($data)) {
		foreach ($data as &$data_set) {
			$data_set = format_date($data_set, $format);
		}
		unset($data_set);
	}
	else if (is_string($data))
		$data = date($format, strtotime($data));
	else if (is_numeric($data))
		$data = date($format, $data);

	return $data;
}

function date_from_offset($offset) {
	if ($offset >= 0)
		return date('Y-m-d', strtotime(date('Y-m-d') . " + $offset days"));
	else {
		$offset = abs($offset);
		return date('Y-m-d', strtotime(date('Y-m-d') . " - $offset days"));
	}
}

function create_ingredient_hrefs($string, $statistic_keyword, $a_class='') {
	global $cacheDataExplode;
	global $cacheDataIgnore;
	global $dateOffset;

	// sort cacheDataExplode (longer stuff first)
	usort($cacheDataExplode, function($a,$b) {
		return strlen($b) - strlen($a);
	});

	$date = date_from_offset($dateOffset);

	// multi food support (e.g. 1. pizza with ham, 2. pizza with bacon, ..)
	// mark each ingredient by an href linking to search
	$foodMulti = explode_by_array($cacheDataExplode, $string);
	foreach ($foodMulti as &$food) {
		$food = str_ireplace($cacheDataIgnore, '', $food);
		$food = cleanText($food);
	}
	unset($food);
	$foodMulti = array_unique($foodMulti);

	// sort after array length, begin with longest first
	usort($foodMulti, function($a, $b) {
		return strlen($b) - strlen($a);
	});

	$replace_pairs = array();
	if (count($foodMulti) > 1) {
		// build replace pairs
		foreach ($foodMulti as $foodSingle) {
			$foodSingle = str_ireplace($cacheDataIgnore, '', $foodSingle);
			$foodSingle = cleanText($foodSingle);

			if (empty($foodSingle) || strlen($foodSingle) < 3)
				continue;

			$foodSingle_slashes = "\"$foodSingle\"";
			$url = "statistics.php?date=$date&keyword=" . urlencode($statistic_keyword) . "&food=" . urlencode($foodSingle);
			if (isset($_GET['minimal']))
				$url .= '&minimal';
			$replace_pairs[$foodSingle] = "<a class='$a_class' title='Statistik zu $foodSingle_slashes' href='$url'>$foodSingle</a>";
		}
		// replace via strtr and built replace_pairs to avoid
		// double replacements for keywords which appear in other ones like CheeseBurger and Burger
		$string = strtr($string, $replace_pairs);
	}
	// if nothing found, replace whole string with link to stats
	if (empty($replace_pairs)) {
		$string_slashes = "\"$string\"";
		$url = "statistics.php?date=$date&keyword=" . urlencode($statistic_keyword) . "&food=" . urlencode($string);
		if (isset($_GET['minimal']))
			$url .= '&minimal';
		$string = "<a class='$a_class' title='Statistik zu $string_slashes' href='$url'>$string</a>";
	}

	return $string;
}

// gets an anonymized name of an ip
function ip_anonymize($ip = null) {
	global $ip_usernames;

	if (!$ip)
		$ip = get_identifier_ip();
	$ip_original = custom_userid_original_ip();

	// do ip <=> name stuff
	// anonymyze ip
	$ip_parts = explode('.', $ip);
	$ipLast = end($ip_parts);
	for ($i=0; $i<count($ip_parts)-1; $i++)
		$ip_parts[$i] = 'x';
	$ipPrint = implode('.', $ip_parts);
	// set username
	if (isset($ip_usernames[$ip]))
		$ipPrint = $ip_usernames[$ip];
	else if (isset($ip_usernames[$ip_original]))
		$ipPrint = $ip_usernames[$ip_original];
	else if (is_intern_ip())
		$ipPrint = 'Guest_' . $ipLast;
	else
		$ipPrint = 'Unknown/extern IP, check config';

	return $ipPrint;
}

/*
 * check if a get or post variable is set
 */
function is_var($name) {
	if (isset($_POST[$name]))
		return true;
	else if (isset($_GET[$name]))
		return true;
	return false;
}
/*
 * get a variable via post or get
 */
function get_var($name) {
	if (isset($_POST[$name]))
		return trim($_POST[$name]);
	else if (isset($_GET[$name]))
		return trim($_GET[$name]);
	return null;
}

/*
 * tests if a value is between a given range
 */
function in_range($val, $min, $max) {
  return ($val >= $min && $val <= $max);
}

// removes unecessary data (newlines, ..) from html
function html_compress($html) {
	$response = $html;

	// newlines, tabs & carriage return
	$response = str_replace(array("\n", "\t", "\r"), '', $html);
	// convert multiple spaces into one
	$response = preg_replace('/\s+/', ' ', $response);
	// cleanup spaces inside tags
	$response = str_replace(' />', '/>', $response);

	return $response;
}

?>
