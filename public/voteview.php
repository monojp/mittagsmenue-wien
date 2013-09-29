<?php
	require_once('../includes/guihelper.php');

	// redirect if not intern ip
	if (!is_intern_ip()) {
		$server = $_SERVER['SERVER_NAME'];
		header("HTTP/1.0 403 Forbidden");
		header("Location: http://$server");
	}

	require_once('header.php');

	// currently no vote => refresh site all 10 seconds
	if (!show_voting())
		echo '<meta http-equiv="refresh" content="10"></meta>';

	echo '</head><body>';

	// tracking code
	if (isset($tracking_code) && !empty($tracking_code))
		echo $tracking_code;

	// overlay info box on the top
	echo get_overlay_info_html();

	// header text
	echo '<h1>Voting</h1>';
	echo '<a href="/">Zurück zur Übersicht</a>';
	echo '<br /><br />';

	// show minimal (no JS) site notice
	if (isset($_GET['minimal']))
		echo get_minimal_site_notice_html();

	// note dialog
	if (!isset($_GET['minimal']))
		echo get_note_dialog_html();

	if (!isset($_GET['minimal']) && show_voting()) {
		// vote setting opener
		echo get_vote_setting_opener_html();

		// voting setting dialog
		echo get_vote_setting_dialog();
	}

	// weather info
	/*if (!isset($_GET['minimal']))
		echo get_temperature_info_html();*/

	// voting exists
	if (!isset($_GET['minimal']) && show_voting()) {
		// write voteable for JS
		echo '
			<br />
			<div style="display: none" id="show_voting"></div> ' .
				get_vote_div_html() .
			'<div style="clear: both"></div>
		';

		// loading container because JS stuff shown when dom ready
		if (!isset($_GET['minimal']))
			echo get_loading_container_html();
	}
	// voting does not exist
	else
		echo '<span class="error">Aktuell wird nicht gevoted.</span>';

	// no javascript => notice + redirect to minimal site
	if (!isset($_GET['minimal']))
		echo get_noscript_html();

	require_once('footer.php');
?>
