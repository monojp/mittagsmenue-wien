<?php
	require_once('../includes/guihelper.php');
	require_once('header.php');
	echo '</head><body>';

	// overlay info box on the top
	if (is_intern_ip())
		echo get_overlay_info_html();

	// date picker element
	echo '<input type="hidden" id="datePicker" />';
	// current date via get
	echo "<input type='hidden' id='date_GET' value='$date_GET' />";

	if (!isset($_GET['minimal'])) {
		// default values for reset actions
		//echo get_default_location_values_html();
		// location dialog
		echo get_location_dialog_html();
		// note dialog
		echo get_note_dialog_html();
		// voting setting dialog
		echo get_alt_venue_and_vote_setting_dialog();
	}

	// write voteable for JS
	if (show_voting())
		echo '<div style="display: none" id="show_voting"></div>';

	// header text
	$dayName = getGermanDayName();
	$date = date_offsetted('d.m.Y');
	$dayText = "<a id='dateHeader' href='javascript:void(0)' title='Klicken, zum Kalender öffnen'>$dayName $date</a>";
	echo "<span style='font-weight: bold; font-size: 2em'>Mittagsmenü Wien, $dayText</span><br />";

	// show minimal (no JS) site notice
	if (isset($_GET['minimal']))
		echo get_minimal_site_notice_html();
	else {
		// location and alt_venue_and_vote_setting opener
		echo '<div class="dialog_opener_float">' . get_location_opener_html() . get_alt_venue_and_vote_setting_opener_html() . '</div>';
		// weather info
		if (show_weather())
			echo get_temperature_info_html();
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
		//new Salzberg(),
		new DeliciousMonster(),
		new Ausklang(),
		//new Kunsthallencafe(),
		//new NamNamDeli(),
		new Waldviertlerhof(),
		new MensaFreihaus(),
		new MensaSchroedinger(),
		new Stoeger(),
		new tewa(),
		new Tschani(),
		//new Martin(),
	);
	foreach ($venues as $venue) {
		echo $venue;
	}
	echo '</div>';

	echo '<div style="clear: both"></div>';

	// no javascript => notice + redirect to minimal site
	if (!isset($_GET['minimal']))
		echo get_noscript_html();

	// loading container because venues are shown via JS when dom ready
	if (!isset($_GET['minimal']))
		echo get_loading_container_html();

	echo '<div id="noVenueFoundNotifier" style="display: none"><p>Es wurde leider nichts gefunden :(<br />Bitte ändern Sie den Ausgangsort und/oder den Umkreis.</p></div>';

	require_once('footer.php');
?>