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

function get_allergen_info() {
	return '
		<div style="font-weight: smaller">
			<span>Allergeninformation gemäß Codex-Empfehlung:</span>
			<div>
				<div class="allergenDiv">A) glutenhaltiges Getreide</div>
				<div class="allergenDiv">B) Krebstiere</div>
				<div class="allergenDiv">C) Ei</div>
				<div class="allergenDiv">D) Fisch</div>
				<div class="allergenDiv">E) Erdnuss</div>
				<div class="allergenDiv">F) Soja</div>
				<div class="allergenDiv">G) Milch oder Laktose</div>
				<div class="allergenDiv">H) Schalenfrüchte</div>
				<div class="allergenDiv">L) Sellerie</div>
				<div class="allergenDiv">M) Senf</div>
				<div class="allergenDiv">N) Sesam</div>
				<div class="allergenDiv">O) Sulfite</div>
				<div class="allergenDiv">P) Lupinen</div>
				<div class="allergenDiv">R) Weichtiere</div>
			</div>
		</div>
	';
}

function get_location_opener_html() {
	global $city;

	return '
		<div class="subheader_div">
			Lokale rund um <a href="javascript:void(0)" onclick="setLocationDialog(this)" title="Adresse festlegen">
				<span id="location">' . $city . '</span></a> (<a href="javascript:void(0)" onclick="showLocation(this)" title="Google Maps Mashup">Standort-Infos</a>)
		</div>
	';
}

function get_location_dialog_html() {
	global $lat, $lng, $city, $distance;

	return '
		<div style="display: none" id="lat">' . $lat . '</div>
		<div style="display: none" id="lng">' . $lng . '</div>
		<div style="display: none" id="distance_default">' . $distance . '</div>

		<div id="setLocationDialog" class="hidden">
			<form id="locationForm" action="index.php">
				<fieldset>
					<label for="locationInput">Adresse</label>
					<br />
					<input type="text" name="location" id="locationInput" value="' . $city . '" style="width: 100%" />
					<br />
					<a href="javascript:void(0)" onclick="setLocation(\'' . $city . '\');$(\'#setLocationDialog\').dialog(\'close\')">Auf Standard setzen</a> | <a href="javascript:void(0)" onclick="setLocation(null, true);$(\'#setLocationDialog\').dialog(\'close\')">Standort bestimmen</a>
				</fieldset>
				<br />
				<fieldset>
					<label for="distance">Umkreis</label>
					<table style="border-spacing: 0px">
						<tr>
							<td>
								<div id="sliderDistance" style="width: 100px"></div>
							</td>
							<td>
								<input type="number" id="distance" style="width: 50px; margin-left: 10px" />
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<a href="javascript:void(0)" onclick="setDistance(\'' . $distance . '\');$(\'#setLocationDialog\').dialog(\'close\')">Auf Standard setzen</a>
							</td>
						</tr>
					</table>
				</fieldset>
			</form>
		</div>
	';
}

function get_note_dialog_html() {
	$vote = getUserVote();
	$note = isset($vote['special']) ? $vote['special'] : '';
	return '
		<div id="setNoteDialog" class="hidden">
			<form id="noteForm" action="index.php">
				<fieldset>
					<label for="noteInput">Notiz</label>
					<br />
					<input type="text" name="note" id="noteInput" value="' . $note . '" maxlength="' . VOTE_NOTE_MAX_LENGTH . '" style="width: 20em" />
				</fieldset>
			</form>
		</div>
	';
}

function get_special_vote_actions_html() {
	$actions[] = '<a href="javascript:void(0)" onclick="vote_special(\'Verweigerung\')">Verweigerung</a>';
	$actions[] = '<a href="javascript:void(0)" onclick="vote_special(\'Egal\')">Egal</a>';
	$actions[] = '<a href="javascript:void(0)" onclick="setNoteDialog()">Notiz</a>';
	$actions[] = '<a href="javascript:void(0)" onclick="vote_delete()">Löschen</a>';
	return implode(' | ', $actions);
}

function get_alt_venue_and_vote_setting_opener_html() {
	$data[] = '<a href="javascript:void(0)" onclick="setAlternativeVenuesDialog()">Weitere Lokale in der Nähe</a>';
	if (is_intern_ip())
		$data[] = '<a href="javascript:void(0)" onclick="setVoteSettingsDialog()">Spezial-Votes &amp; Einstellungen</a>';

	$data = implode(' | ', $data);
	return "<div class='subheader_div'>$data</div>";
}

function get_piwik_user_id_script() {
	$userId = ip_anonymize();
	$userId = (stripos($userId, 'extern') !== false) ? null : $userId;
	// set piwik user id via js, output html
	return '
		<script type="text/javascript">
			var _paq = _paq || [];
			_paq.push(["setUserId", "' . $userId . '"]);
		</script>
	';
}

function get_alt_venue_and_vote_setting_dialog() {
	global $voting_over_time;

	$voting_over_time_print = date('H:i', $voting_over_time);
	$email_config = user_config_get(get_identifier_ip());
	$email = isset($email_config['email']) ? $email_config['email'] : '';
	$vote_reminder = isset($email_config['vote_reminder']) ? $email_config['vote_reminder'] : false;
	$vote_reminder = filter_var($vote_reminder, FILTER_VALIDATE_BOOLEAN) ? 'checked="checked"' : '';
	$voted_mail_only = isset($email_config['voted_mail_only']) ? $email_config['voted_mail_only'] : false;
	$voted_mail_only = filter_var($voted_mail_only, FILTER_VALIDATE_BOOLEAN) ? 'checked="checked"' : '';

	$custom_userid_gui_output = '';
	$custom_userid = custom_userid_get();
	if (!$custom_userid)
		$custom_userid = custom_userid_current();

	// only show the custom_userid GUI intern
	// otherwise users could lock themselves out from extern
	if (!custom_userid_current() && is_intern_ip()) {
		$custom_userid_url = custom_userid_access_url_get($custom_userid);
		$custom_userid_url = empty($custom_userid_url) ? 'nicht gesetzt' : $custom_userid_url;
		$custom_userid_gui_output = '
			<br />
			<fieldset>
				<label>Externe Zugriffs-URL</label>
				<p id="custom_userid_url">
				' . $custom_userid_url . '
				</p>
				<a href="javascript:void(0)" onclick="custom_userid_generate()">Neue URL generieren</a>
			</fieldset>
		';
	}

	return '
		<div style="display: none" id="userid">' . $custom_userid . '</div>
		<div id="setAlternativeVenuesDialog" class="hidden">
			<fieldset>
				<p id="div_voting_alt_loader">
					Lade Restaurants in der Umgebung <br /><img src="imagesCommon/loader.gif" width="160" height="24" alt="" style="vertical-align: middle" />
				</p>
				<br />
				<table id="table_voting_alt" style="width: 100% ! important">
					<thead>
						<tr>
							<th>Name</th>
							<th>Distanz</th>
							<th>Rating</th>
							<th data-dynatable-no-sort="data-dynatable-no-sort">Aktionen</th>
						</tr>
					</thead>
				</table>
			</fieldset>
		</div>
		<div id="setVoteSettingsDialog" class="hidden">
			<fieldset>
				<label>Spezial-Votes</label>
				<p>
				' . get_special_vote_actions_html() . '
				</p>
				<label for="name">Benutzername</label>
				<p>
					<input type="text" name="name" id="name" value="' . ip_anonymize() . '" style="width: 100%" />
				</p>
			</fieldset>
			<br />
			<fieldset>
				<label for="email">Email-Benachrichtigung an</label>
				<p>
					<input type="email" name="email" id="email" value="' . $email . '" style="width: 100%" title="wird versendet um ' . $voting_over_time_print . '" />
				</p>
				<label for="vote_reminder" title="Wurde noch nicht gevoted, so wird kurz vor Ende eine Erinnerungs-Email versendet">
					<input type="checkbox" name="vote_reminder" id="vote_reminder" ' . $vote_reminder . ' /> Vote-Erinnerung per Email kurz vor Ende, falls nicht gevoted
				</label>
				<br />
				<label for="voted_mail_only" title="Benachrichtigungs-Emails werden nur versendet, wenn vorher aktiv gevoted wurde">
					<input type="checkbox" name="voted_mail_only" id="voted_mail_only" ' . $voted_mail_only . ' /> Email(s) nur versenden, wenn selbst gevoted
				</label>
			</fieldset>
			' . $custom_userid_gui_output . '
		</div>
	';
}

function get_vote_div_html() {
	global $voting_over_time;
	$voting_over_time_print = date('H:i', $voting_over_time);

	$vote_loader = '';

	if (time() >= $voting_over_time)
		$voting_info_text = "Das Voting hat um $voting_over_time_print geendet!";
	else {
		$vote_loader = '
		<div style="margin: 0px 5px">
			Warte auf weitere Stimmen
			<img src="imagesCommon/loader_small.gif" width="100" height="15" alt="" style="vertical-align: middle" />
		</div>
		';
		$voting_info_text = "Hinweis: Das Voting endet um $voting_over_time_print!";
	}

	return '
		<div id="voting_over_time" class="hidden">' . $voting_over_time . '</div>
		<div id="voting_over_time_print" class="hidden">' . $voting_over_time_print . '</div>
		<div style="clear: both"></div>
		<div id="dialog_vote_summary">
			<div id="dialog_ajax_data"></div>
			<div style="margin: 5px">' . get_special_vote_actions_html() . '</div>
			' . $vote_loader . '
			<div class="error" style="margin: 5px" title="In den Einstellungen kann eine Email-Benachrichtigung aktiviert werden, welche zur gegebenen Zeit versandt wird.">
				' . $voting_info_text . '
			</div>
		</div>
	';
}

function get_loading_container_html() {
	return '
		<div id="loadingContainer"><img src="imagesCommon/loader.gif" width="160" height="24" alt="" style="vertical-align: middle" /></div>
	';
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
			Hinweis: Aktuell sehen Sie eine vereinfachte Version dieser Seite. Bitte aktivieren Sie JavaScript und besuchen Sie die Vollversion <a href="' . $url . '">hier</a>.
		</span>
	';
}
