<?php

require_once(__DIR__ . '/includes.php');
require_once(__DIR__ . '/vote.inc.php');
require_once(__DIR__ . '/customuserid.inc.php');

// default location for JS
$city = LOCATION_FALLBACK;
$lat = LOCATION_FALLBACK_LAT;
$lng = LOCATION_FALLBACK_LNG;
$distance = LOCATION_DEFAULT_DISTANCE;
$lat = str_replace(',', '.', $lat);
$lng = str_replace(',', '.', $lng);

function get_venues_html() {
	$response = '';

	// loading container because venues are shown via JS when dom ready
	if (!isset($_GET['minimal']))
		$response .= get_loading_container_html();

	$venues = [
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
		new FalkensteinerStueberl(),
		//new Lambrecht(),
		new CafeAmacord(),
		//new Gondola(),
		new RadioCafe(),
		new Stoeger(),
		//new MINIRESTAURANT(),
	];
	foreach ($venues as $venue) {
		$response .= $venue;
	}
	return $response;
}

function get_header_html() {
	// start output buffering with custom html compress handler
	ob_start('html_compress');

	$response = '<!DOCTYPE html>
		<html lang="de">
		<head>
			<title>' . META_KEYWORDS . '</title>
			<meta charset="UTF-8" />
			<meta name="robots" content="INDEX,FOLLOW" />
			<meta name="keywords" content="' . META_KEYWORDS . '" />
			<meta name="description" content="' .  META_DESCRIPTION . '" />
			<meta name="viewport" content="width=device-width, initial-scale=1" />';

	// basic css and javascript
	$mobile_datepicker_css       = USE_MINIMZED_JS_CSS_HTML ? '/datepicker/jquery.mobile.datepicker.min.css' : '/datepicker/jquery.mobile.datepicker.css';
	$mobile_datepicker_theme_css = USE_MINIMZED_JS_CSS_HTML ? '/datepicker/jquery.mobile.datepicker.theme.min.css' : '/datepicker/jquery.mobile.datepicker.theme.css';
	$throbber_css = USE_MINIMZED_JS_CSS_HTML ? '/css/throbber.min.css' : '/css/throbber.css';
	$basic_css = USE_MINIMZED_JS_CSS_HTML ? '/css/basic.min.css' : '/css/basic.css';
	$head_load_js = USE_MINIMZED_JS_CSS_HTML ? '/js/head.load.min.js' : '/js/head.load.js';
	$datepicker_js = USE_MINIMZED_JS_CSS_HTML ? '/datepicker/datepicker.min.js' : '/datepicker/datepicker.js';
	$mobile_datepicker_js = USE_MINIMZED_JS_CSS_HTML ? '/datepicker/jquery.mobile.datepicker.min.js' : '/datepicker/jquery.mobile.datepicker.js';
	$basic_js = USE_MINIMZED_JS_CSS_HTML ? '/js/basic.min.js' : '/js/basic.js';

	// css
	$response .='
		<link rel="stylesheet" type="text/css" href="' . cacheSafeUrl($basic_css) . '" />
		<link rel="stylesheet" type="text/css" href="' . cacheSafeUrl($throbber_css) . '" />
		<link rel="stylesheet" type="text/css" href="' . cacheSafeUrl('/jquery_mobile/jquery.mobile-1.4.5.min.css') . '" />
		<link rel="stylesheet" type="text/css" href="' . cacheSafeUrl($mobile_datepicker_css) . '" />
		<link rel="stylesheet" type="text/css" href="' . cacheSafeUrl($mobile_datepicker_theme_css) . '" />
		<link rel="stylesheet" type="text/css" href="' . cacheSafeUrl('/css/jquery.dataTables.min.css') . '" />
		<link rel="stylesheet" type="text/css" href="' . cacheSafeUrl('/css/jquery.textcomplete.css') . '" />
		<link rel="stylesheet" type="text/css" href="' . cacheSafeUrl('/emojione/emojione.min.css') . '" />
		<link rel="stylesheet" type="text/css" href="' . cacheSafeUrl('/emojione/sprites/emojione.sprites.css') . '" />';
	// javascript
	if (!isset($_GET['minimal']))
		$response .= '<script src="' . cacheSafeUrl($head_load_js) . '" type="text/javascript"></script>
			<script type="text/javascript">
				var init_unhandled = false;
				head.load([{jquery: "' . cacheSafeUrl('/js/jquery-2.1.4.min.js') . '"}], function() {
					$("#page_main").on("pagecreate", function() {
						$(document).trigger("init");
						init_unhandled = true;
					});
					head.load([
						{jquery_mobile: "' . cacheSafeUrl('/jquery_mobile/jquery.mobile-1.4.5.min.js') . '"},
						{datepicker: "' . cacheSafeUrl($datepicker_js) . '"},
						{mobile_datepicker: "' . cacheSafeUrl($mobile_datepicker_js) . '"},
						{basic: "' . cacheSafeUrl($basic_js) . '"},
						{jquery_cookie: "' . cacheSafeUrl('/js/jquery.cookie.js') . '"},
						{jquery_datatables: "' . cacheSafeUrl('/js/jquery.dataTables.min.js') . '"},
						{textcomplete: "' . cacheSafeUrl('/js/jquery.textcomplete.min.js') . '"},
						{emojione: "' . cacheSafeUrl('/emojione/emojione.min.js') . '"}
					]);
				});
			</script>';

	// minimal site and votes allowed, refresh every 10s
	if (isset($_GET['minimal']) && show_voting()) {
		$response .= '<meta http-equiv="refresh" content="10" />';
	}

	// no js fallback to minimal site refresh
	if (!isset($_GET['minimal'])) {
		$url = build_url('?minimal');
	}

	$response .= '</head><body>';

	// header
	/*$response .= "<div data-role='header'>
		<h1>
			" . SITE_TITLE . ", <input type='date' id='date' title='' value='" . date_offsetted('Y-m-d') . "' data-role='none' />
		</h1>
		<a id='location' href='javascript:void(0)' onclick='setLocationDialog(this)' data-role='button' data-inline='true' data-mini='true' data-icon='location'
				title='Adresse festlegen' class='ui-btn-right'>" . LOCATION_FALLBACK . "</a>
	</div>";

	$response .= "<div role='main' class='ui-content'>";*/

	return $response;
}
function get_footer_html() {
	global $tracking_code, $dateOffset;

	//$response = "</div>";
	$response = '';

	if ($_SERVER['REQUEST_URI'] === '/stats/') {
		$outputs[] = "<a href='/' data-ajax='false'>Home</a>";
	} else {
		$outputs[] = "<a href='" . build_url('/stats/') . "' data-ajax='false'>Stats</a>";
	}

	if (CONTACT_HREF) {
		$outputs[] = '<a href="' . htmlspecialchars(CONTACT_HREF) . '" target="_blank" data-rel="dialog">Kontakt</a>';
	}
	if (IMPRESSUM_HREF) {
		$outputs[] = '<a href="' . htmlspecialchars(IMPRESSUM_HREF) . '" target="_blank" data-rel="dialog">Impressum</a>';
	}
	if (PRIVACY_INFO) {
		$outputs[] = '<a href="javascript:void(0)" title="' . htmlspecialchars(PRIVACY_INFO) . '" data-rel="dialog">Datenschutz-Hinweis</a>';
	}
	if (!isset($_GET['minimal'])) {
		$outputs[] = "<a href='" . build_url('?minimal') . "' title='Zeigt eine Version dieser Seite ohne JavaScript an' data-ajax='false'>Minimal</a>";
	}
	$outputs[] = '<a href="https://github.com/monojp/mittagsmenue-wien/" target="_blank" data-rel="dialog">Source</a>';

	$response .= '<div data-role="footer">
		<h2>' . implode(' | ', $outputs) . '</h2>
	</div>';

	$response .= "${tracking_code}";

	return $response;
}

function get_page_location() {
	global $lat, $lng, $city, $distance;

	return '
		<div class="hidden" id="lat">' . $lat . '</div>
		<div class="hidden" id="lng">' . $lng . '</div>
		<div class="hidden" id="distance_default">' . $distance . '</div>

		<div id="setLocationDialog" class="hidden" data-role="page">
			<div data-role="header">
				<h1>Adresse festlegen</h1>
			</div>
			<div data-role="content">
				<form id="locationForm" action="index.php">
					<fieldset>
						<label for="locationInput">Adresse</label>
						<input type="text" name="location" id="locationInput" value="' . $city . '" style="width: 100%" />
						<a href="javascript:void(0)" onclick="setLocation(\'' . $city . '\');$(\'#setLocationDialog\').dialog(\'close\')">Auf Standard setzen</a> | <a href="javascript:void(0)" onclick="setLocation(null, true);$(\'#setLocationDialog\').dialog(\'close\')">Standort bestimmen</a>
					</fieldset>
					<br>
					<fieldset>
						<label for="distance">Umkreis</label>
						<input type="range" name="distance" id="distance" value="' . $distance . '" min="0" max="10000" step="100" />
						<a href="javascript:void(0)" onclick="setDistance(\'' . $distance . '\');$(\'#setLocationDialog\').dialog(\'close\')">Auf Standard setzen</a>
					</fieldset>
					<br>
					<button data-icon="check">Speichern</button>
				</form>
			</div>
			<div data-role="footer"></div>
		</div>
	';
}

function get_page_note() {
	global $timestamp;
	$ip        = get_identifier_ip();
	$vote_data = VoteHandler_MySql::getInstance($timestamp)->get(date(VOTE_DATE_FORMAT, $timestamp), $ip);
	$note      = isset($vote_data[$ip]['special']) ? $vote_data[$ip]['special'] : '';
	return '
		<div id="setNoteDialog" class="hidden" data-role="page">
			<div data-role="header">
				<h1>Notiz erstellen</h1>
			</div>
			<div data-role="content">
				<form id="noteForm" action="index.php">
					<fieldset>
						<label for="noteInput">
							Notiz / <a href="http://emojione.com/" target="_blank">Emoji</a>
						</label>
						<small>Hinweis: <a href="http://emojione.com/" target="_blank">Emojis</a> werden durch Eingabe von ":" gesucht/vorgeschlagen</small>
						<input type="text" name="note" id="noteInput" value="' . $note . '" maxlength="' . VOTE_NOTE_MAX_LENGTH . '" style="width: 20em" />
						<br>
						<span>Vorschau</span>
						<div id="notePreview" style="margin: .5em 0"></div>
						<br>
						<button data-icon="check">Speichern</button>
					</fieldset>
				</form>
			</div>
			<div data-role="footer"></div>
		</div>
	';
}

function get_special_vote_actions_html() {
	$actions[] = '<a href="javascript:void(0)" onclick="vote_special(\'Verweigerung\')">Verweigerung</a>';
	$actions[] = '<a href="javascript:void(0)" onclick="vote_special(\'Egal\')">Egal</a>';
	$actions[] = '<a href="#setNoteDialog" data-rel="dialog" data-transition="pop">Notiz</a>';
	$actions[] = '<a href="javascript:void(0)" onclick="vote_delete()">Löschen</a>';
	return implode(' | ', $actions);
}

function get_alt_venue_html() {
	return "<fieldset>
		<div id='div_voting_alt_loader'>
			Lade Restaurants in der Umgebung
			<br>
			<div class='throbber middle'>Lade...</div>
		</div>
		<table id='table_voting_alt' data-role='table' class='ui-responsive'>
			<thead></thead>
			<tbody></tbody>
		</table>
	</fieldset>";
}

function get_vote_setting_html() {
	global $voting_over_time;

	if (!is_intern_ip()) {
		return;
	}

	$ip = get_identifier_ip();

	$user_config = UserHandler_MySql::getInstance()->get($ip);
	$user_config = is_array($user_config) ? reset($user_config) : null;

	$voting_over_time_print = date('H:i', $voting_over_time);
	$email = isset($user_config['email']) ? $user_config['email'] : '';
	$vote_reminder = isset($user_config['vote_reminder']) ? $user_config['vote_reminder'] : false;
	$vote_reminder = filter_var($vote_reminder, FILTER_VALIDATE_BOOLEAN) ? 'checked="checked"' : '';
	$voted_mail_only = isset($user_config['voted_mail_only']) ? $user_config['voted_mail_only'] : false;
	$voted_mail_only = filter_var($voted_mail_only, FILTER_VALIDATE_BOOLEAN) ? 'checked="checked"' : '';
	$vote_always_show = isset($user_config['vote_always_show']) ? $user_config['vote_always_show'] : false;
	$vote_always_show = filter_var($vote_always_show, FILTER_VALIDATE_BOOLEAN) ? 'checked="checked"' : '';

	$custom_userid_gui_output = '';
	$custom_userid = isset($user_config['custom_userid']) ? $user_config['custom_userid'] : '';
	if (!$custom_userid) {
		$custom_userid = custom_userid_current();
	}

	// only show the custom_userid GUI intern
	// otherwise users could lock themselves out from extern
	if (!custom_userid_current()) {
		$custom_userid_url = custom_userid_access_url_get($custom_userid);
		$custom_userid_url = empty($custom_userid_url) ? 'nicht gesetzt' : $custom_userid_url;
		$custom_userid_gui_output = '
			<br>
			<fieldset>
				<label>Externe Zugriffs-URL</label>
				<p id="custom_userid_url">
					<a href="' . $custom_userid_url . '" target="_blank" data-ajax="false">' . $custom_userid_url . '</a>
				</p>
			</fieldset>
		';
	}

	return '
		<div style="display: none" id="userid">' . $custom_userid . '</div>
			<fieldset>
				<label for="name">Benutzername</label>
				<p>
					<input type="text" name="name" id="name" value="' . ip_anonymize($ip, $user_config) . '" style="width: 100%" />
				</p>
			</fieldset>
			<br>
			<fieldset>
				<label for="email">Email-Benachrichtigung an</label>
				<p>
					<input type="email" name="email" id="email" value="' . $email . '" style="width: 100%" title="wird versendet um ' . $voting_over_time_print . '" />
				</p>
				<label for="vote_reminder" title="Wurde noch nicht gevoted, so wird kurz vor Ende eine Erinnerungs-Email versendet">
					<input type="checkbox" name="vote_reminder" id="vote_reminder" ' . $vote_reminder . ' /> Vote-Erinnerung per Email kurz vor Ende
				</label>
				<label for="voted_mail_only" title="Benachrichtigungs-Emails werden nur versendet, wenn vorher aktiv gevoted wurde">
					<input type="checkbox" name="voted_mail_only" id="voted_mail_only" ' . $voted_mail_only . ' /> Ergebnis-Email nur versenden, wenn teilgenommen wurde
				</label>
				<label for="vote_always_show" title="Zeigt den Voting-Dialog immer offen an. Erspart evtl. einen Klick.">
					<input type="checkbox" name="vote_always_show" id="vote_always_show" ' . $vote_always_show . ' /> Voting immer offen anzeigen
				</label>
			</fieldset>
			' . $custom_userid_gui_output . '
			<br>
			<button data-icon="check" onclick="vote_settings_save();">Speichern</button>';
}

function get_button_vote_summary_toggle_html() {
	if (!show_voting())
		return '';
	return "<button id='button_vote_summary_toggle' data-icon='carat-r'>Voting Anzeigen</button>";
}

function get_vote_div_html() {
	global $voting_over_time;
	$voting_over_time_print = date('H:i', $voting_over_time);

	$vote_loader = '';

	if (!show_voting())
		return '';

	// show minimal (no JS) site notice
	if (isset($_GET['minimal'])) {
		echo get_minimal_site_notice_html();
		// also show the current voting status
		$votes = getAllVotes();
		if (!empty($votes))
			return vote_summary_html($votes, false);
	}

	if (!vote_allowed())
		$voting_info_text = "Das Voting hat um $voting_over_time_print geendet!";
	else {
		$vote_loader = '
		<div style="margin: 0px 5px">
			Warte auf weitere Stimmen
			<div class="throbber middle">Lade...</div>
		</div>
		';
		$voting_info_text = "Hinweis: Das Voting endet um $voting_over_time_print!";
	}

	return '
		<div style="display: none" id="show_voting"></div>
		<div id="voting_over_time" class="hidden">' . $voting_over_time . '</div>
		<div id="voting_over_time_print" class="hidden">' . $voting_over_time_print . '</div>
		<div style="clear: both"></div>
		<div id="dialog_ajax_data"></div>
		<div style="margin: 5px">' . get_special_vote_actions_html() . '</div>
		' . $vote_loader . '
		<div class="error" style="margin: 5px" title="In den Einstellungen kann eine Email-Benachrichtigung aktiviert werden, welche zur gegebenen Zeit versandt wird.">
			' . $voting_info_text . '
		</div>
	';
}

function get_loading_container_html() {
	return '<div id="loadingContainer">
			<div class="throbber middle">Lade...</div>
		</div>';
}

function get_minimal_site_notice_html() {
	global $dateOffset;

	$url = '?date=' . date_from_offset($dateOffset);
	if (isset($_GET['keyword']))
		$url .= '&keyword=' . urlencode($_GET['keyword']);
	if (isset($_GET['food']))
		$url .= '&food=' . urlencode($_GET['food']);
	$url = htmlspecialchars($url);

	return '
		<span class="error">
			Hinweis: Aktuell sehen Sie eine vereinfachte Version dieser Seite. Bitte aktivieren Sie JavaScript und besuchen Sie die Vollversion <a href="' . $url . '" data-ajax="false">hier</a>.
		</span>
	';
}
