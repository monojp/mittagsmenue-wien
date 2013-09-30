<?php

require_once(dirname(__FILE__) . '/../includes/includes.php');

define('WEATHER_TMP_PATH', TMP_PATH . 'weather_cache.json');
define('WEATHER_IMG_PATH_NAME', 'imagesWeather/');
define('WEATHER_IMG_PATH', dirname(__FILE__) . '/../public/' . WEATHER_IMG_PATH_NAME);

function queryTemperature() {
	$temp = $desc = $desc_detail = null;

	$html = file_get_contents('http://www.zamg.ac.at/cms/de/wetter/wetterwerte-analysen/wien/?' . uniqid());

	// get description
	$start = strposAfter($html, 'alt="Wien Innere Stadt:');
	$end = strpos($html, '"', $start);
	$desc = substr($html, $start, $end - $start);
	$desc = cleanText($desc);

	// get temperature
	$start = strposAfter($html, 'title="Wien Innere Stadt, Temp:');
	$end = strpos($html, '&deg;C', $start);
	$temp = substr($html, $start, $end - $start);
	$temp = cleanText($temp);

	// get icon url
	$start = strposAfter($html, 'http://www.zamg.ac.at/cms/de/wetter/wetterwerte-analysen/tawes-verlaufsgraphiken/wien_innere_stadt/temperatur');
	$start = strposAfter($html, 'src="', $start);
	$end = strpos($html, '"', $start);
	$iconUrl = substr($html, $start, $end - $start);
	$iconUrl = cleanText($iconUrl);
	$iconFilename = basename($iconUrl);
	// get icon data and save locally
	$iconData = file_get_contents($iconUrl);
	file_put_contents(WEATHER_IMG_PATH . $iconFilename, $iconData);
	$iconLocalUrl = WEATHER_IMG_PATH_NAME . $iconFilename;
	// trim icon with imagemagick to minimize not used space
	shell_exec("convert $iconLocalUrl -trim $iconLocalUrl 2> /dev/null");

	$html = file_get_contents('http://www.zamg.ac.at/cms/de/wetter/wetter-oesterreich/wien/?' . uniqid());

	// get forecast for the day
	$start = strposAfter($html, 'prognosenText">');
	$end = strpos($html, '</div>', $start);
	$desc_detail = substr($html, $start, $end - $start);
	$desc_detail = cleanText(strip_tags($desc_detail));

	return array(
		'temp'        => $temp,
		'desc'        => $desc,
		'desc_detail' => $desc_detail,
		'icon_url'    => $iconLocalUrl
	);
}

function getTemperatureString($show_image = true, $use_cache = true) {

	if (!$use_cache || !is_file(WEATHER_TMP_PATH))
		$data = null;
	else
		$data = json_decode(file_get_contents(WEATHER_TMP_PATH), true);

	// refresh if cache out of date
	// or cache invalid
	if (
		empty($data) ||
		!isset($data['temp']) ||
		!isset($data['desc']) ||
		!isset($data['time']) ||
		!isset($data['desc_detail']) ||
		!isset($data['timestamp']) ||
		!isset($data['icon_url']) ||
		abs(time() - $data['timestamp']) / 60 > 30
	) {
		$weather = queryTemperature();
		$temp = $weather['temp'];
		$desc = $weather['desc'];
		$desc_detail = $weather['desc_detail'];
		$icon_url = $weather['icon_url'];
		$time = date('H:i');

		// write to cache
		file_put_contents(WEATHER_TMP_PATH, json_encode(array(
			'temp'        => $temp,
			'desc'        => $desc,
			'time'        => $time,
			'desc_detail' => $desc_detail,
			'icon_url'    => $icon_url,
			'timestamp'   => time()
		)));
	}
	else {
		$temp = $data['temp'];
		$desc = $data['desc'];
		$time = $data['time'];
		$desc_detail = $data['desc_detail'];
		$icon_url = $data['icon_url'];
	}

	if ($show_image) {
		$image_size = getimagesize(WEATHER_IMG_PATH . basename($icon_url));
		$image_width = $image_size[0];
		$image_height = $image_size[1];

		// embed image data
		$icon_url = 'data:image/png;base64,' . base64_encode(file_get_contents(WEATHER_IMG_PATH . basename($icon_url)));

		return "
			<div title='Wien Innere Stadt: $desc  ($time) <br /> $desc_detail' style='text-align: center; display: inline-table'>
				<img src='$icon_url' width='$image_width' height='$image_height' />
				<div style='font-size: 0.8em; padding: 2px; border: 1px solid black; border-radius: 2px; box-shadow: 1px 1px 5px lightgray'>$temp °C</div>
			</div>
		";
	}

	return "Aktuelles Wetter: <a title='$desc_detail' href='javascript:void(0)' style='cursor: default'>$temp °C | $desc ($time)</a>";

}

?>
