<?

require_once('../includes/includes.php');
require_once('../includes/vote.inc.php');

// default location for JS
$city = LOCATION_FALLBACK;
$lat = LOCATION_FALLBACK_LAT;
$lng = LOCATION_FALLBACK_LNG;
$distance = LOCATION_DEFAULT_DISTANCE;
$lat = str_replace(',', '.', $lat);
$lng = str_replace(',', '.', $lng);

/*function get_default_location_values_html() {
	global $city, $lat, $lng;

	return "
		<div id='default_city' style='display: none'>$city</div>
		<div id='default_lat' style='display: none'>$lat</div>
		<div id='default_lng' style='display: none'>$lng</div>
	";
}*/

function get_overlay_info_html() {
	global $overlay_info_data;
	global $overlay_info_version;

	if (empty($overlay_info_data) || empty($overlay_info_version))
		return;

	return "
		<div id='overlay_info_version' style='display: none'>$overlay_info_version</div>
		<div id='overlay_info' style='display: none'>$overlay_info_data</div>
	";
}

function get_temperature_info_html() {
	return "
		<div id='weatherContainer' class='dialog_opener_float' style='margin-top: -7px'></div>
		<script type='text/javascript'>
			head.ready('scripts', function() {
				$.ajax({
					type: 'POST',
					url:  'weather.php',
					data: { 'action': 'getString' },
					dataType: 'json',
					success: function(result) {
						$(document).ready(function() {
							$('#weatherContainer').html(result);
							$('a').tooltip();
						});
					},
					error: function() {
						var errMsg = $(document.createElement('span'));
						errMsg.attr('class', 'error');
						errMsg.html('Fehler beim Abfragen der Wetter-Daten :(');
						$('#weatherContainer').empty();
						$('#weatherContainer').append(errMsg);
					}
				});
			});
		</script>
	";
}

function get_location_opener_html() {
	global $city;

	return '
		<div class="subheader_div">
			Lokale rund um <a href="javascript:void(0)" onclick="setLocationDialog(this)" title="Adresse festlegen">
				<span id="location">' . $city . '</span></a>
			(<a href="javascript:void(0)" onclick="showLocation(this)" title="Google Maps Mashup">Standort-Infos</a>)
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
					<label for="location">Adresse</label>
					<br />
					<input type="text" name="location" id="locationInput" value="' . $city . '" style="width: 100%"></input>
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
								<input type="text" id="distance" style="width: 50px; margin-left: 10px"></input>
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
	return '
		<div id="setNoteDialog" class="hidden">
			<form id="noteForm" action="index.php">
				<fieldset>
					<label for="noteInput">Notiz</label>
					<br />
					<input type="text" name="note" id="noteInput" value="" maxlength="64" style="width: 100%"></input>
				</fieldset>
			</form>
		</div>
	';
}

function get_special_vote_actions_html() {
	$actions[] = '<a href="javascript:void(0)" onclick="vote_special(\'Verweigerung\')">Verweigerung</a>';
	$actions[] = '<a href="javascript:void(0)" onclick="vote_special(\'Egal\')">Egal</a>';
	$actions[] = '<a href="javascript:void(0)" onclick="setNoteDialog()">Notiz setzen</a>';
	$actions[] = '<a href="javascript:void(0)" onclick="vote_delete()">Vote Löschen</a>';
	return implode(' | ', $actions);
}

function get_alt_venue_and_vote_setting_opener_html() {
	$data[] = '<a href="javascript:void(0)" onclick="setAlternativeVenuesDialog()">Weitere Lokale in der Nähe</a>';
	if (is_intern_ip())
		$data[] = '<a href="javascript:void(0)" onclick="setVoteSettingsDialog()">Spezial-Votes & Einstellungen</a>';

	$data = implode(' | ', $data);
	return "<div class='subheader_div'>$data</div>";
}

function get_alt_venue_and_vote_setting_dialog() {
	global $voting_over_time;

	$voting_over_time_print = date('H:i', $voting_over_time);
	$email_config = email_config_get($_SERVER['REMOTE_ADDR']);
	$email = isset($email_config['email']) ? $email_config['email'] : '';
	$vote_reminder = isset($email_config['vote_reminder']) ? $email_config['vote_reminder'] : false;
	$vote_reminder = filter_var($vote_reminder, FILTER_VALIDATE_BOOLEAN) ? 'checked="checked"' : '';
	$voted_mail_only = isset($email_config['voted_mail_only']) ? $email_config['voted_mail_only'] : false;
	$voted_mail_only = filter_var($voted_mail_only, FILTER_VALIDATE_BOOLEAN) ? 'checked="checked"' : '';

	return '
		<div id="setAlternativeVenuesDialog" class="hidden">
			<fieldset>
				<p id="div_voting_alt_loader">
					Lade Restaurants in der Umgebung <br /><img src="imagesCommon/loader.gif" width="160" height="24" alt="ladebalken" style="vertical-align: middle" />
				</p>
				<br />
				<table id="table_voting_alt" style="width: 100% ! important"><tr><td></td></tr></table>
			</fieldset>
		</div>
		<div id="setVoteSettingsDialog" class="hidden">
			<fieldset>
				<label>Spezial-Votes</label>
				<p>
				' . get_special_vote_actions_html() . '
				</p>
			</fieldset>
			<br />
			<fieldset>
				<label for="email">Email-Benachrichtigung an</label>
				<p>
					<input type="text" name="email" id="email" value="' . $email . '" style="width: 100%"
						title="wird versendet um ' . $voting_over_time_print . '"></input>
				</p>
				<label title="Wurde noch nicht gevoted, so wird kurz vor Ende eine Erinnerungs-Email versendet">
					<input type="checkbox" name="vote_reminder" id="vote_reminder" ' . $vote_reminder . ' />
					Vote-Erinnerung per Email kurz vor Ende, falls nicht gevoted
				</label>
				<br />
				<label title="Benachrichtigungs-Emails werden nur versendet, wenn vorher aktiv gevoted wurde">
					<input type="checkbox" name="voted_mail_only" id="voted_mail_only" ' . $voted_mail_only . ' />
					Email(s) nur versenden, wenn selbst gevoted
				</label>
			</fieldset>
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
		<audio id="audio_notification" class="hidden">
			<source src="sounds/receive.wav" type="audio/ogg">
		</audio>
		<div style="margin: 0px 10px">
			<b>Quick-Vote:</b><br /><br />
			' . get_special_vote_actions_html() . '
		</div>
		<div style="margin: 0px 10px">
			Warte auf weitere Stimmen
			<br />
			<img src="imagesCommon/loader.gif" width="160" height="24" alt="ladebalken" style="vertical-align: middle" />
		</div>
		';
		$voting_info_text = "Hinweis: Das Voting endet um $voting_over_time_print!";
	}

	return '
		<div id="voting_over_time" class="hidden">' . $voting_over_time . '</div>
		<div id="voting_over_time_print" class="hidden">' . $voting_over_time_print . '</div>
		<div id="dialog" class="dialog_vote_summary">
			<div id="dialog_ajax_data"></div>
			' . $vote_loader . '
			<div class="error" style="margin: 10px" title="In den Einstellungen kann eine Email-Benachrichtigung aktiviert werden, welche zur gegebenen Zeit versandt wird.">
				' . $voting_info_text . '
			</div>
		</div>
	';
}

function get_noscript_html() {
	return '
		<noscript>
			Diese Seite benötigt JavaScript!
			Bitte aktivieren Sie JavaScript oder verwenden Sie die Minimal-Version.
		</noscript>
	';
}

function get_loading_container_html() {
	return '
		<div id="loadingContainer"><img src="imagesCommon/loader.gif" width="160" height="24" alt="ladebalken" style="vertical-align: middle" /></div>
	';
}

function get_minimal_site_notice_html() {
	global $dateOffset;

	$url = '?date=' . date_from_offset($dateOffset);

	return '
		<span class="error">
			Hinweis: Aktuell sehen Sie eine vereinfachte Version dieser Seite. Bitte aktivieren Sie JavaScript und besuchen Sie die Vollversion <a href="' . $url . '">hier</a>.
		</span>
	';
}

?>
