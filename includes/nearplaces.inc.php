<?php

require_once(__DIR__ . '/includes.php');

$venues = [
	'le Pho' => [
		'lat' => 48.192134,
		'lng' => 16.348653,
		'website' => 'http://www.le-pho.at/',
	],
	'Disco Volante' => [
		'lat' => 48.191429,
		'lng' => 16.347603,
		'website' => 'http://www.disco-volante.at/',
	],
	'Kuishimbo' => [
		'lat' => 48.197427,
		'lng' => 16.359181,
		'website' => 'https://www.facebook.com/pages/Kuishimbo/67343688168',
	],
	'Kojiro - Sushi-Bar' => [
		'lat' => 48.198927,
		'lng' => 16.364725,
		'website' => 'https://plus.google.com/106777516565797933298/about',
	],
	'Zweitbester' => [
		'lat' => 48.195292,
		'lng' => 16.363092,
		'website' => 'http://www.zweitbester.at/',
	],
	'Chinazentrum' => [
		'lat' => 48.197340,
		'lng' => 16.358725,
		'website' => 'http://www.chinazentrum-naschmarkt.at/',
	],
	'Gondola' => [
		'lat' => 48.189628,
		'lng' => 16.352214,
		'website' => 'http://www.gondola.at/',
	],
	'Nagoya Sushi' => [
		'lat' => 48.196901,
		'lng' => 16.365892,
		'website' => 'http://www.nagoyasushi.at/',
	],
	'Karma Ramen' => [
		'lat' => 48.196341,
		'lng' => 16.357740,
		'website' => 'http://www.karmaramen.at/',
	],
	'Al Chile!' => [
		'lat' => 48.194904,
		'lng' => 16.350911,
		'website' => 'http://www.al-chile.info/',
	],
	'Duspara' => [
		'lat' => 48.184198,
		'lng' => 16.361702,
		'website' => 'http://www.duspara.at/',
	],
	'Mosquito Mexican Restaurant' => [
		'lat' => 48.192429,
		'lng' => 16.360060,
		'website' => 'http://www.mosquito-mexican-restaurant-wien.at/',
	],
	'Vietnam Bistro' => [
		'lat' => 48.189569,
		'lng' => 16.352020,
		'website' => 'https://www.tripadvisor.at/Restaurant_Review-g190454-d2236734-Reviews-Vietnam_Bistro-Vienna.html',
	],
	'ghisallo' => [
		'lat' => 48.189222,
		'lng' => 16.351730,
		'website' => 'http://www.ghisallo.cc/',
	],
	'Pasham Kebap' => [
		'lat' => 48.1883225,
		'lng' => 16.3517071,
		'website' => 'https://www.yelp.de/biz/pasham-wien',
	],
	'Pferdefleischer Schuller Rudolf' => [
		'lat' => 48.183731,
		'lng' => 16.355765,
		'website' => 'http://diefleischer.at/1050/schuller-1050.html',
	],
	'Anadolu Backshop' => [
		'lat' => 48.184739,
		'lng' => 16.352949,
		'website' => 'https://www.yelp.de/biz/anadolu-backshop-wien',
	],
	'Thai Isaan Kitchen' => [
		'lat' => 48.192937,
		'lng' => 16.349373,
		'website' => 'http://www.thai-isaan.at/',
	],
	'Xu\'s Cooking' => [
		'lat' => 48.188364,
		'lng' => 16.351627,
		'website' => 'http://www.xu-s-cooking-wien.at/',
	],
	'JAS' => [
		'lat' => 48.190641,
		'lng' => 16.348541,
		'website' => 'http://www.jas-restaurant.at/',
	],
	'Tawa Indian Restaurant' => [
		'lat' => 48.189375,
		'lng' => 16.355823,
		'website' => 'https://www.tripadvisor.at/Restaurant_Review-g190454-d12637347-Reviews-Tawa_Indian_Restaurant-Vienna.html',
	],
	'Restaurant Stöger' => [
		'lat' => 48.190313,
		'lng' => 16.354877,
		'website' => 'http://www.zumstoeger.at/',
	],
	'Semendria' => [
		'lat' => 48.189132,
		'lng' => 16.350553,
		'website' => 'http://www.semendria.at/',
	],
	'Colombo Hoppers' => [
		'lat' => 48.189199,
		'lng' => 16.350790,
		'website' => 'http://www.colombohoppers.com/',
	],
	'Ströck' => [
		'lat' => 48.188621,
		'lng' => 16.351564,
		'website' => 'https://stroeck.at/',
	],
	'Hofer' => [
		'lat' => 48.188951,
		'lng' => 16.352531,
		'website' => 'https://www.hofer.at/',
	],
	'Zum Lieben Augustin' => [
                'lat' => 48.187229,
                'lng' => 16.352319,
                'website' => 'http://www.gasthaus-zumliebenaugustin.at/',
        ],
	'YAK+YETI' => [
		'lat' => 48.194207,
		'lng' => 16.351047,
		'website' => 'http://www.yakundyeti.at/',
	],
	'Pizzeria Maria Rosa – Die beste Pizza am ganzen Siebenbrunnenplatz' => [
		'lat' => 48.18566,
		'lng' => 16.35342,
		'website' => 'https://www.7brunnen.com/',
	],
];

function nearby_search($lat, $lng, $radius) {
	global $venues;

	$lat = sprintf('%F', $lat);
	$lng = sprintf('%F', $lng);

	// add custom venues that are in reach
	$response = [];
	foreach ($venues as $name => $data) {
		$lat_venue = sprintf('%F', $data['lat']);
		$lng_venue = sprintf('%F', $data['lng']);
		if (distance($lat_venue, $lng_venue, $lat, $lng, false) <= $radius) {
			$maps_href = "https://www.openstreetmap.org/directions?engine=graphhopper_foot&route=$lat,$lng;$lat_venue,$lng_venue";
			$name_escaped = htmlspecialchars($name, ENT_QUOTES);
			$name_escaped = str_replace("'", '', $name_escaped);

			$actions = "<a href='${maps_href}' class='no_decoration lat_lng_link' target='_blank'>
				<div data-enhanced='true' class='ui-link ui-btn ui-icon-location ui-btn-icon-notext ui-btn-inline ui-shadow ui-corner-all' title='OpenStreetMap Route'>OpenStreetMap Route</div>
			</a>";
			if (show_voting()) {
				$actions .= "<div data-enhanced='true' class='ui-link ui-btn ui-icon-plus ui-btn-icon-notext ui-btn-inline ui-shadow ui-corner-all' onclick='vote_up(\"{$name_escaped}\")' title='Vote Up'>Vote up</div>"
						. "<div data-enhanced='true' class='ui-link ui-btn ui-icon-minus ui-btn-icon-notext ui-btn-inline ui-shadow ui-corner-all' onclick='vote_down(\"{$name_escaped}\")' title='Vote Down'>Vote Down</div>";
			}

			$response[] = [
				'name' => $name,
				"<a href='{$data['website']}' title='Homepage' target='_blank'>{$name_escaped}</a>",
				round(distance($lat, $lng, $lat_venue, $lng_venue, false) * 1000),
				$actions,
			];
		}
	}

	return $response;
}

function nearby_venue_valid($name) {
	global $venues;

	return array_key_exists($name, $venues);
}

function nearby_venue_website($name) {
	global $venues;

	if (!nearby_venue_valid($name)) {
		return null;
	}

	return $venues[$name]['website'];
}
