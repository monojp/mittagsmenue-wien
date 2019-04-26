<?php

require_once(__DIR__ . '/includes.php');

function get_venues_html() {
	$response = '';

	// loading container because venues are shown via JS when dom ready
	if (!isset($_GET['minimal'])) {
		$response .= get_loading_container_html();
	}

	$venues = [
		new App\Parser\Venue\SchlossquadratMargareta(),
		new App\Parser\Venue\SchlossquadratSilberwirt(),
		new App\Parser\Venue\SchlossquadratCuadro(),
		new App\Parser\Venue\AltesFassl(),
		new App\Parser\Venue\TasteOfIndia(),
		new App\Parser\Venue\Waldviertlerhof(),
		new App\Parser\Venue\Woracziczky(),
		new App\Parser\Venue\Stoeger(),
		new App\Parser\Venue\Erbsenzaehlerei(),
		new App\Parser\Venue\Bierometer2(),
	];

	// sort venues after distance
	usort($venues, function ($a, $b) {
		$distance_a = \App\Legacy\ContainerHelper::getInstance()->get(\App\Service\GeoService::class)->distance(
		    getenv('LOCATION_FALLBACK_LAT'),
            getenv('LOCATION_FALLBACK_LNG'),
            $a->addressLat,
			$a->addressLng
        );
		$distance_b = \App\Legacy\ContainerHelper::getInstance()->get(\App\Service\GeoService::class)->distance(
            getenv('LOCATION_FALLBACK_LAT'),
            getenv('LOCATION_FALLBACK_LNG'),
            $b->addressLat,
			$b->addressLng
        );

		if ($distance_a === $distance_b) {
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
	$response = '<!DOCTYPE html>
		<html lang="de">
		<head>
			<title>' . getenv('SITE_TITLE') . '</title>
			<meta charset="UTF-8" />
			<meta name="robots" content="INDEX,FOLLOW" />
			<meta name="keywords" content="' . getenv('META_KEYWORDS') . '" />
			<meta name="description" content="' .  getenv('META_DESCRIPTION') . '" />
			<meta name="viewport" content="width=device-width, initial-scale=1" />';

	// css
	$response .='
		<link rel="stylesheet" type="text/css" href="/css/basic.css" />
		<link rel="stylesheet" type="text/css" href="/css/throbber.css" />
		<link rel="stylesheet" type="text/css" href="/jquery_mobile/jquery.mobile-1.4.5.min.css" />
		<link rel="stylesheet" type="text/css" href="/css/jquery.dataTables.min.css" />';
	// javascript
	if (!isset($_GET['minimal']))
		$response .= '
			<script src="/js/jquery-3.1.0.min.js"></script>
			<script src="/js/jquery-migrate-3.0.0.js"></script>
			<script src="/jquery_mobile/jquery.mobile-1.4.5.min.js"></script>
			<script src="/js/basic.js"></script>
			<script src="/js/jquery.dataTables.min.js" async="async"></script>
			<script src="/js/jquery-textcomplete/jquery.textcomplete.min.js" async="async"></script>
		';

	// minimal site and votes allowed, refresh every 10s
	if (isset($_GET['minimal']) && \App\Legacy\ContainerHelper::getInstance()->get(\App\Service\VoteService::class)->votingDisplayAllowed(get_ip())) {
		$response .= '<meta http-equiv="refresh" content="10" />';
	}

	$response .= '</head><body>';
	$response .= '<div class="hidden" id="SEARCH_PROVIDER">' . getenv('SEARCH_PROVIDER') . '</div>';
	return $response;
}
function get_footer_html() {
	$response = '';

	if (getenv('CONTACT_HREF')) {
		$outputs[] = '<a href="' . htmlspecialchars(getenv('CONTACT_HREF')) . '" target="_blank" data-rel="dialog">Kontakt</a>';
	}
	if (getenv('IMPRESSUM_HREF')) {
		$outputs[] = '<a href="' . htmlspecialchars(getenv('IMPRESSUM_HREF')) . '" target="_blank" data-rel="dialog">Impressum</a>';
	}
	if (getenv('PRIVACY_INFO')) {
		$outputs[] = '<a href="javascript:void(0)" title="' . htmlspecialchars(getenv('PRIVACY_INFO')) . '" data-rel="dialog">Datenschutz-Hinweis</a>';
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
	$vote_data = \App\Legacy\ContainerHelper::getInstance()->get(\App\Service\VoteService::class)->getAllVoteData($timestamp, $ip, 'special');
	$note      = isset($vote_data[$ip]['votes']['special']) ? $vote_data[$ip]['votes']['special'] : '';
	return '
		<div id="setNoteDialog" class="hidden" data-role="page">
			<div data-role="header">
				<h1>Notiz erstellen</h1>
			</div>
			<div data-role="content">
				<form id="noteForm" action="index.php">
					<fieldset>
						<input type="text" name="note" id="noteInput" value="' . $note . '" />
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
	$voting_over_time = getenv('VOTING_OVER_TIME');

	$ip = get_ip();
    if (!\App\Legacy\ContainerHelper::getInstance()->get(\App\Service\IpService::class)->isInternIp($ip)) {
        return '';
    }

    $foodUser = \App\Legacy\ContainerHelper::getInstance()->get(\App\Service\UserService::class)->getUser($ip);
	$vote_reminder = $foodUser->getVoteReminder() ? 'checked="checked"' : '';
	$voted_mail_only = $foodUser->getVotedMailOnly() ? 'checked="checked"' : '';
	$vote_always_show = $foodUser->getVoteAlwaysShow() ? 'checked="checked"' : '';

	$response = '
        <fieldset>
            <label for="name">Benutzername</label>
            <p>
                <input type="text" name="name" id="name" value="' . $foodUser->getName() . '" style="width: 100%" />
            </p>
        </fieldset>
        <br>
        <fieldset>
            <label for="email">Email-Benachrichtigung an</label>
            <p>
                <input type="email" name="email" id="email" value="' . $foodUser->getEmail() . '" style="width: 100%" title="wird versendet um ' . $voting_over_time . '" />
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
				$("#setVoteSettingsDialog input").on("change", function() { vote_settings_save(); });
			</script>';
	}
	return $response;
}

function get_button_vote_summary_toggle_html() {
	if (!\App\Legacy\ContainerHelper::getInstance()->get(\App\Service\VoteService::class)->votingDisplayAllowed(get_ip()))
		return '';
	return "<button id='button_vote_summary_toggle' data-icon='carat-r'>Voting Anzeigen</button>";
}

function get_vote_div_html() {
    global $timestamp;
	$voting_over_time = getenv('VOTING_OVER_TIME');

	$vote_loader = '';

	if (!\App\Legacy\ContainerHelper::getInstance()->get(\App\Service\VoteService::class)->votingDisplayAllowed(get_ip()))
		return '';

	// show minimal (no JS) site notice
	if (isset($_GET['minimal'])) {
		echo get_minimal_site_notice_html();
		// also show the current voting status
		$votes = \App\Legacy\ContainerHelper::getInstance()->get(\App\Service\VoteService::class)->getAllVoteData($timestamp);
		if (!empty($votes))
			return \App\Legacy\ContainerHelper::getInstance()->get(\App\Service\VoteService::class)->summaryHtml(get_ip(), $votes);
	}

	if (!\App\Legacy\ContainerHelper::getInstance()->get(\App\Service\VoteService::class)->votingAllowed(get_ip()))
		$voting_info_text = "Das Voting hat um $voting_over_time geendet!";
	else {
		$vote_loader = '
		<div style="margin: 0px 5px">
			Warte auf weitere Stimmen
			<div class="throbber middle">Lade...</div>
		</div>
		';
		$voting_info_text = "Hinweis: Das Voting endet um $voting_over_time!";
	}

	return '
		<div style="display: none" id="show_voting"></div>
		<div id="voting_over_time" class="hidden">' . $voting_over_time . '</div>
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

	$url = '?date=' . \App\Legacy\ContainerHelper::getInstance()->get(\App\Parser\TimeService::class)->dateFromOffset($dateOffset);
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
