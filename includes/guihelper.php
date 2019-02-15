<?php

require_once(__DIR__ . '/includes.php');
require_once(__DIR__ . '/vote.inc.php');
require_once(__DIR__ . '/BannerHandler_MySql.php');

function get_banner_html() {
	// query for the banner of the day, return immediately if no banner configured
	if (empty($banner = BannerHandler_MySql::getInstance()->get(date_offsetted('w')))) {
		return '';
	}

	$img = "<img src='{$banner['url']}' />";
	// link to vote up if voting enabled and venue is set
	if (show_voting() && $banner['venue']) {
		$venue_class = htmlspecialchars($banner['venue'], ENT_QUOTES);
		return "<a href='javascript:void(0)' onclick='vote_up(\"{$venue_class}\")'>{$img}</a>";
	} else {
		return $img;
	}
}

function get_venues_html() {
	$response = '';

	// loading container because venues are shown via JS when dom ready
	if (!isset($_GET['minimal'])) {
		$response .= get_loading_container_html();
	}

	$venues = [
		new SchlossquadratMargareta(),
		new SchlossquadratSilberwirt(),
		new SchlossquadratCuadro(),
		new AltesFassl(),
		//new HaasBeisl(),
		new TasteOfIndia(),
		new Ausklang(),
		//new NamNam(),
		new Waldviertlerhof(),
		//new MensaFreihaus(),
		//new MensaSchroedinger(),
		new Woracziczky(),
		//new CoteSud(),
		//new FalkensteinerStueberl(),
		//new Lambrecht(),
		//new CafeAmacord(), // zu mittag nicht mehr offen
		//new Gondola(),
		//new RadioCafe(), // encoding-probleme
		new Stoeger(),
		//new MINIRESTAURANT(),
		new Erbsenzaehlerei(),
		new Bierometer2(),
		//new Duspara(),
		//new Stefan2(),
	];

	// sort venues after distance
	usort($venues, function ($a, $b) {
		$distance_a = distance(LOCATION_FALLBACK_LAT,
			LOCATION_FALLBACK_LNG, $a->addressLat,
			$a->addressLng);
		$distance_b = distance(LOCATION_FALLBACK_LAT,
			LOCATION_FALLBACK_LNG, $b->addressLat,
			$b->addressLng);

		if ($distance_a == $distance_b) {
			return 0;
		}

		return ($distance_a < $distance_b) ? -1 : 1;
	});

	// output venues
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

	// css
	$response .='
		<link rel="stylesheet" type="text/css" href="' . cacheSafeUrl('/css/basic.css') . '" />
		<link rel="stylesheet" type="text/css" href="' . cacheSafeUrl('/css/throbber.css') . '" />
		<link rel="stylesheet" type="text/css" href="' . cacheSafeUrl('/jquery_mobile/jquery.mobile-1.4.5.min.css') . '" />
		<link rel="stylesheet" type="text/css" href="' . cacheSafeUrl('/css/jquery.dataTables.min.css') . '" />';
	// javascript
	if (!isset($_GET['minimal']))
		$response .= '
			<script src="' . cacheSafeUrl('/js/jquery-3.1.0.min.js') . '"></script>
			<script src="' . cacheSafeUrl('/js/jquery-migrate-3.0.0.js') . '"></script>
			<script src="' . cacheSafeUrl('/jquery_mobile/jquery.mobile-1.4.5.min.js') . '"></script>
			<script src="' . cacheSafeUrl('/js/basic.js') . '"></script>
			<script src="' . cacheSafeUrl('/js/jquery.dataTables.min.js') . '" async="async"></script>
			<script src="' . cacheSafeUrl('/js/jquery-textcomplete/jquery.textcomplete.min.js') . '" async="async"></script>
		';

	// minimal site and votes allowed, refresh every 10s
	if (isset($_GET['minimal']) && show_voting()) {
		$response .= '<meta http-equiv="refresh" content="10" />';
	}

	// no js fallback to minimal site refresh
	if (!isset($_GET['minimal'])) {
		$url = build_url('?minimal');
	}

	$response .= '</head><body>';
	$response .= '<div class="hidden" id="SEARCH_PROVIDER">' . SEARCH_PROVIDER . '</div>';
	return $response;
}
function get_footer_html() {
	global $dateOffset;

	//$response = "</div>";
	$response = '';

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

	return $response;
}

function get_page_note() {
	global $timestamp;
	$ip        = get_ip();
	$vote_data = getAllVotes($ip, 'special');
	$note      = isset($vote_data[$ip]['votes']['special']) ? $vote_data[$ip]['votes']['special'] : '';
	return '
		<div id="setNoteDialog" class="hidden" data-role="page">
			<div data-role="header">
				<h1>Notiz erstellen</h1>
			</div>
			<div data-role="content">
				<form id="noteForm" action="index.php">
					<fieldset>
						<input type="text" name="note" id="noteInput" value="' . $note . '" maxlength="' . VOTE_NOTE_MAX_LENGTH . '" />
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

	$ip = get_ip();
	$user_config = UserHandler_MySql::getInstance()->get($ip);
	if (!is_intern_ip($ip)) {
		return;
	}

	$user_config = reset($user_config);

	$voting_over_time_print = date('H:i', $voting_over_time);
	$email = isset($user_config['email']) ? $user_config['email'] : '';
	$vote_reminder = isset($user_config['vote_reminder']) ? $user_config['vote_reminder'] : false;
	$vote_reminder = filter_var($vote_reminder, FILTER_VALIDATE_BOOLEAN) ? 'checked="checked"' : '';
	$voted_mail_only = isset($user_config['voted_mail_only']) ? $user_config['voted_mail_only'] : false;
	$voted_mail_only = filter_var($voted_mail_only, FILTER_VALIDATE_BOOLEAN) ? 'checked="checked"' : '';
	$vote_always_show = isset($user_config['vote_always_show']) ? $user_config['vote_always_show'] : false;
	$vote_always_show = filter_var($vote_always_show, FILTER_VALIDATE_BOOLEAN) ? 'checked="checked"' : '';

	$response = '
        <fieldset>
            <label for="name">Benutzername</label>
            <p>
                <input type="text" name="name" id="name" value="' . $user_config['name'] . '" style="width: 100%" />
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
        <br>';
	if (!isset($_GET['minimal'])) {
		$response .= '<script>
				$("#setVoteSettingsDialog input").on("change keyup", function() { vote_settings_save(); });
			</script>';
	}
	return $response;
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
