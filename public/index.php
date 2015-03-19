<?php

require_once(__DIR__ . '/../includes/guihelper.php');
require_once('header.php');

// minimal site and votes allowed, refresh every 10s
if (isset($_GET['minimal']) && show_voting())
	echo '<meta http-equiv="refresh" content="10" />';

// no js fallback to minimal site refresh
if (!isset($_GET['minimal']))
	$url = build_minimal_url();

echo '</head><body>';

if (!isset($_GET['minimal'])) {
	// default values for reset actions
	//echo get_default_location_values_html();
	// location dialog
	echo get_location_dialog_html();
	// note dialog
	echo get_note_dialog_html();
	// voting setting dialog
	echo get_alt_venue_and_vote_setting_dialog();
	// piwik user id script
	echo get_piwik_user_id_script();
}

// write voteable for JS
if (show_voting())
	echo '<div style="display: none" id="show_voting"></div>';

// header text
$dayName = getGermanDayName();
$date = date_offsetted('Y-m-d');
$dayText = "<label for='date'>$dayName</label> <input type='date' id='date' title='' value='$date' />";
echo "<span style='font-weight: bold; font-size: 2em'>Mittagsmenü Wien, $dayText</span><br />";

// show minimal (no JS) site notice
if (isset($_GET['minimal'])) {
	echo get_minimal_site_notice_html();
	// also show the current voting status if voting allowed
	if (show_voting()) {
		$votes = getAllVotes();
		if (!empty($votes))
			echo '<div id="dialog_vote_summary" style="display: table">' . vote_summary_html($votes, false) . '</div>';
	}
}
else {
	// location and alt_venue_and_vote_setting opener
	echo '<div class="dialog_opener_float">' . get_location_opener_html() . get_alt_venue_and_vote_setting_opener_html() . '</div>';
	if (show_voting())
		echo get_vote_div_html();
}

echo '<div style="clear: both"></div>';

echo '<div id="venueContainer">';
$venues = array(
	new SchlossquadratMargareta(),
	new SchlossquadratSilberwirt(),
	new AltesFassl(),
	new HaasBeisl(),
	new TasteOfIndia(),
	new DeliciousMonster(),
	new Ausklang(),
	new NamNamDeli(),
	new Waldviertlerhof(),
	new MensaFreihaus(),
	new MensaSchroedinger(),
	new Woracziczky(),
	//new CoteSud(),
	//new FalkensteinerStueberl(),
	new Lambrecht(),
);
foreach ($venues as $venue) {
	echo $venue;
}
echo '</div>';

echo '<div style="clear: both"></div>';

// loading container because venues are shown via JS when dom ready
if (!isset($_GET['minimal']))
	echo get_loading_container_html();

echo '<div id="noVenueFoundNotifier" style="display: none"><p>Es wurde leider nichts gefunden :(<br />Bitte ändern Sie den Ausgangsort und/oder den Umkreis.</p></div>';

// changelog
if (!empty($changelog)) {
	echo '<div id="changelog" style="display: none; max-height: 15em ! important;">';
	krsort($changelog);
	foreach ($changelog as $time => $entry) {
		$time  = date('Y-m-d', $time);
		$entry = htmlspecialchars($entry);
		echo "<h4 style='margin:.5em 0;'>${time}</h4><div>${entry}</div>";
	}
	echo '<div id="changelog_latest" style="display: none;">' . reset(array_keys($changelog)) . '</div>';
	echo '</div>';
}

require_once('footer.php');
