<?

require_once('../includes/includes.php');

// default location for JS
$city = LOCATION_FALLBACK;
$lat = LOCATION_FALLBACK_LAT;
$lng = LOCATION_FALLBACK_LNG;
$lat = str_replace(',', '.', $lat);
$lng = str_replace(',', '.', $lng);

function get_overlay_info_html() {
	global $overlay_info_data;
	global $overlay_info_version;

	if (empty($overlay_info_data) || empty($overlay_info_version))
		return;

	return "
		<div id='overlay_info_version' style='display: none'>
			$overlay_info_version
		</div>
		<div id='overlay_info' style='display: none'>
			$overlay_info_data
		</div>
	";
}

function get_temperature_info_html() {
	return "
		<div id='weatherContainer' class='subheader_div'>
			Aktuelles Wetter: <img src='imagesCommon/loader.gif' width='160' height='24' alt='ladebalken' style='vertical-align: middle' />
		</div>
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
	global $lat, $lng, $city;

	return '
		<div style="display: none" id="lat">' . $lat . '</div>
		<div style="display: none" id="lng">' . $lng . '</div>

		<div id="setLocationDialog" class="hidden">
			<form id="locationForm" action="index.php">
				<fieldset>
					<label for="location">Adresse</label>
					<br />
					<input type="text" name="location" id="locationInput" value="' . $city . '" style="width: 100%"></input>
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

function get_vote_setting_opener_html() {
	return '
		<div class="subheader_div">
			Anzeigen der <a href="javascript:void(0)" onclick="setVoteSettingsDialog()">Spezial-Votes & Einstellungen</a>
		</div>
	';
}

function get_vote_setting_dialog() {
	global $voting_over_time;
	global $voting_alt_locations;
	$voting_over_time_print = date('H:i', $voting_over_time);
	$email = emails_get($_SERVER['REMOTE_ADDR']);

	$voting_alt_table = '<table>';
	foreach ($voting_alt_locations as $title => $data) {
		$href = $data['href'];
		$address = urlencode($data['address']);

		$voting_alt_table .= "<tr>
			<td style='font-weight: bold'>
				<a href='$href' target='_blank'>$title</a>
			</td>
			<td>
				<a href='https://maps.google.com/maps?q=$address' target='_blank'>
					<span class='icon sprite sprite-icon_pin_map' title='Google Maps'></span>
				</a>
			</td>
			<td>
				<a href='javascript:void(0)' onclick='vote_up(\"$title\")'>
					<span class='icon sprite sprite-icon_hand_pro' title='Vote Up'></span>
				</a>
			</td>
			<td>
				<a href='javascript:void(0)' onclick='vote_down(\"$title\")'>
					<span class='icon sprite sprite-icon_hand_contra' title='Vote Down'></span>
				</a>
			</td>
		</tr>";
	}
	$voting_alt_table .= '</table>';

	return '
		<div id="setVoteSettingsDialog" class="hidden">
			<fieldset>
				<label>Alternativ-Votes</label>
				<br />
				' . $voting_alt_table . '
			</fieldset>
			<br />
			<fieldset>
				<label>Spezial-Votes</label>
				<p>
					<a href="javascript:void(0)" onclick="vote_special(\'Verweigerung\')">Verweigerung</a> |
					<a href="javascript:void(0)" onclick="vote_special(\'Egal\')">Egal</a> |
					<a href="javascript:void(0)" onclick="setNoteDialog()">Notiz setzen</a> |
					<a href="javascript:void(0)" onclick="vote_delete()">Vote Löschen</a>
				</p>
			</fieldset>
			<br />
			<fieldset>
				<label for="email">Email-Benachrichtigung an</label>
				<br />
				<input type="text" name="email" id="email" value="' . $email . '" style="width: 100%"
					title="wird versendet um ' . $voting_over_time_print . '">
				</input>
			</fieldset>
		</div>
	';
}

function get_vote_div_html() {
	global $voting_over_time;
	$voting_over_time_print = date('H:i', $voting_over_time);

	if (time() >= $voting_over_time)
		$voting_info_text = "Das Voting hat um $voting_over_time_print geendet!";
	else
		$voting_info_text = "Hinweis: Das Voting endet um $voting_over_time_print!";

	return '
		<div id="voting_over_time" class="hidden">' . $voting_over_time . '</div>
		<div id="voting_over_time_print" class="hidden">' . $voting_over_time_print . '</div>
		<div id="dialog" title="Voting" class="dialog_vote_summary">
			<div id="dialog_ajax_data"></div>
			<div style="margin: 0px 10px">
				<b>Quick-Vote:</b><br /><br />
				<a href="javascript:void(0)" onclick="setNoteDialog()">Notiz setzen</a> |
				<a href="javascript:void(0)" onclick="vote_delete()">Vote Löschen</a>
			</div>
			<br />
			<div style="margin: 0px 10px">
				Warte auf weitere Stimmen
				<br />
				<img src="imagesCommon/loader.gif" width="160" height="24" alt="ladebalken" style="vertical-align: middle" />
			</div>
			<div class="error" style="margin: 10px" title="In den Einstellungen kann eine Email-Benachrichtigung aktiviert werden, welche zur gegebenen Zeit versandt wird.">
				' . $voting_info_text . '
			</div>
		</div>
	';
}

function get_noscript_html() {
	return '
		<noscript>
			<span class="error">
				Diese Seite benötigt JavaScript!<br />
				Aktivieren Sie JavaScript oder verwenden Sie die Minimalversion unter '. $_SERVER['SERVER_NAME'] . '?minimal
			</span>
		</noscript>
	';
}

function get_loading_container_html() {
	return '
		<div id="loadingContainer"><img src="imagesCommon/loader.gif" width="160" height="24" alt="ladebalken" style="vertical-align: middle" /></div>
	';
}

function get_minimal_site_notice_html() {
	return '
		<span class="error">
			Hinweis: Aktuell sehen Sie eine vereinfachte Version dieser Seite. Bitte aktivieren Sie JavaScript und besuchen Sie die Vollversion <a href="/">hier</a>.
		</span>
	';
}

?>
