<?php

require_once(__DIR__ . '/../includes/guihelper.php');

// print header
echo get_header_html();

// main page with header and tabbed content
$vote_style = isset($_GET['minimal']) ? 'display: table;' : '';
echo "
<div id='page_main' data-role='page'>
	<div data-role='header'>
		<h1>
			" . SITE_TITLE . ', ' . getGermanDayName() . " <input type='text' id='date' title='' value='" . date_offsetted('Y-m-d') . "' data-inline='false' data-role='date' />
		</h1>
		<a id='location' href='#setLocationDialog' data-role='button' data-inline='true' data-mini='true' data-icon='location'
				data-rel='dialog' data-transition='pop' title='Adresse festlegen' class='ui-btn-right'>" . LOCATION_FALLBACK . "</a>
	</div>
	<div data-role='main' class='ui-content'>
		<div data-role='tabs' id='tabs'>
			<div data-role='navbar'>
				<ul>
					<li><a href='#one' data-ajax='false' data-icon='home'>Lokale</a></li>
					<li><a href='#two' data-ajax='false' data-icon='cloud'>Alternativen</a></li>
					<li><a href='#three' data-ajax='false' data-icon='gear'>Einstellungen</a></li>
				</ul>
			</div>
			" . get_button_vote_summary_toggle_html() . "
			<div id='dialog_vote_summary' style='${vote_style}'>" . get_vote_div_html() . "</div>
			<div id='one' style='padding: 1em 0;'>
				<div id='noVenueFoundNotifier' style='display: none'>
					Es wurde leider nichts gefunden :(<br />Bitte ändern Sie den Ausgangsort und/oder den Umkreis.
				</div>
				<div id='venueContainer'>" . get_venues_html() ."</div>
			</div>
			<div id='two' class='hidden' style='padding: 1em 0;'>
				<div id='setAlternativeVenuesDialog'>" . get_alt_venue_html() ."</div>
			</div>
			<div id='three' class='hidden' style='padding: 1em 0;'>
				<div id='setVoteSettingsDialog'>" . get_vote_setting_html() ."</div>
			</div>
		</div>
	</div>
	<div data-role='footer'>" . get_footer_html() . "</div>
</div>";

// other pages used as dialogs
if (!isset($_GET['minimal'])) {
	// location dialog
	echo get_page_location();
	// note dialog
	echo get_page_note();
}

//echo '<div style="clear: both"></div>';

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

// close html
echo "</body></html>";
