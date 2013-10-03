var intervalVotes = false;
var venueDivDisplay = null;
var usedGeolocation = false;
var oldVoteData = null;
var voting_over_interval_multiplier = 1;
var venues_ajax_query = Array();

function isMobileDevice() {
	return /Android|webOS|iPhone|iPad|iPod|BlackBerry|MIDP|Nokia|J2ME/i.test(navigator.userAgent);
}

// jquery keys extension for old IE
$.extend({
	keys: function(obj) {
		var a = [];
		$.each(obj, function(k){ a.push(k) });
		return a;
	}
});

// sends vote action (vote_up, vote_down, vote_get) and identifier (delete, restaurant name, ..) to server
function vote_helper(action, identifier, note) {
	$.ajax({
		type: "POST",
		url:  "vote.php",
		data: { "action": action, "identifier": identifier, "note": note},
		dataType: "json",
		success: function(result) {

			// increase interval multiplier to reduce server load
			if (intervalVotes)
				clearInterval(intervalVotes);
			if (typeof result.voting_over != 'undefined' && result.voting_over || !result || typeof result.alert != 'undefined')
				voting_over_interval_multiplier += 0.1;
			else
				voting_over_interval_multiplier = 1;
			intervalVotes = setInterval(function(){vote_get()}, Math.floor(5000 * voting_over_interval_multiplier));

			// exit, if we got the same as before
			if (typeof JSON != 'undefined' && JSON.stringify(oldVoteData) == JSON.stringify(result))
				return;

			// alert from server (e.g. error)
			if (typeof result.alert != 'undefined') {
				alert(result.alert);
			}
			// got valid vote result
			else if (typeof result.html != 'undefined') {
				$("#dialog_ajax_data").html(result.html);
				$("#dialog").show();
				$("#dialog").effect('highlight');
			}
			// no | empty result => hide voting dialog
			else {
				if (intervalVotes)
					$("#dialog").hide();
			}
			oldVoteData = result;
		},
		error: function() {
			alert('Fehler beim Setzen des Votes.');
		}
	});
}
// vote up
function vote_up(identifier) {
	vote_helper('vote_up', identifier, null);
}
// vote down
function vote_down(identifier) {
	vote_helper('vote_down', identifier, null);
}
// vote special
function vote_special(identifier) {
	vote_helper('vote_special', identifier, null);
}
// set note
function vote_set_note(note) {
	vote_helper('vote_set_note', null, note);
}
// get votes
function vote_get() {
	vote_helper('vote_get', null);
}
// delete vote
function vote_delete() {
	vote_helper('vote_delete', null);
}
// got (lat / long) location => get address from it
function positionHandler(position) {
	lat = position.coords.latitude;
	lng = position.coords.longitude;

	// get address via ajax
	$.ajax({
		type: "POST",
		url:  "locator.php",
		data: { "action": "latlngToAddress", "lat": lat, "lng": lng},
		dataType: "json",
		success: function(result) {

			if (result && typeof result.address != 'undefined' && result.address) {
				usedGeolocation = true;
				$('#location').html(result.address);
				$('#locationInput').val(result.address);
				sortVenuesAfterPosition(lat, lng);
			}
		},
		error: function() {
			sortVenuesAfterPosition(lat, lng);
		}
	});
}
// error or user denied location access
function positionErrorHandler(error) {
	sortVenuesAfterPosition($('#lat').html(), $('#lng').html());
}
// calculates the distance between two lat/lng points
function distanceLatLng(lat1, lng1, lat2, lng2) {
	lat1 = parseFloat(lat1);
	lng1 = parseFloat(lng1);
	lat2 = parseFloat(lat2);
	lng2 = parseFloat(lng2);
	var pi80 = Math.PI / 180;
	lat1 *= pi80;
	lng1 *= pi80;
	lat2 *= pi80;
	lng2 *= pi80;

	var r = 6372.797; // mean radius of Earth in km
	var dlat = lat2 - lat1;
	var dlng = lng2 - lng1;
	var a = Math.sin(dlat / 2) * Math.sin(dlat / 2) + Math.cos(lat1) * Math.cos(lat2) * Math.sin(dlng / 2) * Math.sin(dlng / 2);
	var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
	var km = r * c;

	return km;
}
// sort venues according to given lat lng of user
function sortVenuesAfterPosition(lat, lng) {

	// set lat | lng in document
	$('#lat').html(lat);
	$('#lng').html(lng);

	// get location diff for all venues
	var locationDiffs = new Array();
	$.each($('[class="venueDiv"]'), function() {
		var id = $(this).prop('id');
		var latVenue = $(this).children('.lat').html();
		var lngVenue = $(this).children('.lng').html();

		locationDiffs[id] = distanceLatLng(lat, lng, latVenue, lngVenue);
	});

	// load all venues in array for sorting
	var venueSortArray = $('[class="venueDiv"]');

	// sort array according to location diff
	venueSortArray.sort(function (a, b) {
		var idA = $(a).prop('id');
		var idB = $(b).prop('id');
		var diffA = locationDiffs[idA];
		var diffB = locationDiffs[idB];

		if (diffA > diffB) {
			return 1;
		}
		else if (diffA < diffB) {
			return -1;
		}
		else {
			return 0;
		}
	});

	// load sorted venues back in venue container
	$('#venueContainer').html(venueSortArray);

	// locationReady event
	$(document).trigger('locationReady');
}

function setLocation(location, force_geolocation) {
	// location via geolocation
	if (!location || force_geolocation) {
		// use geolocation via client only on mobile devices
		if ((isMobileDevice() || force_geolocation) && navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(positionHandler, positionErrorHandler, {timeout: 5000});
		}
		else {
			// sort venues
			sortVenuesAfterPosition($('#lat').html(), $('#lng').html());

			// locationReady event
			usedGeolocation = false;
			$(document).trigger('locationReady');
		}
		$.removeCookie('location');
		return;
	}
	usedGeolocation = false;

	// get lat / lng via ajax
	$.ajax({
		type: "POST",
		url:  "locator.php",
		data: { "action": "addressToLatLong", "address": location},
		dataType: "json",
		success: function(result) {

			if (result && typeof result.lat != 'undefined' && result.lat && typeof result.lng != 'undefined' && result.lng) {
				// custom location
				$('#location').html(location);
				$('#locationInput').val(location);

				// sort venues
				sortVenuesAfterPosition(result.lat, result.lng);

				// save custom location in cookie
				$.cookie('location', location, { expires: 7 });
			}
			else {
				$('#locationInput').val($('#location').html());
				alert('Keine Geo-Daten zu dieser Adresse gefunden.');
			}
		},
		error: function() {
			alert('Fehler beim Abrufen der Geo-Position. Bitte Internetverbindung überprüfen.');
		}
	});
}
// set location by user => get lat / lng from it => sort venues
function setLocationDialog(el) {
	// show dialog
	$('#setLocationDialog').dialog({
		modal: true,
		title: "Adresse festlegen",
		buttons: {
			"Ok": function() {
				var location = $('#locationInput').val();
				setLocation(location, false);
				$('#setLocationDialog').dialog("close");
				$(this).dialog("close");
			},
			"Abbrechen": function() {
				$(this).dialog("close");
			}
		},
		width: "380"
	});
	// close shown tooltip
	$(el).tooltip("close");
}
function setDistance(distance) {
	if (typeof distance != 'undefined') {
		// set slider
		$('#sliderDistance').slider("option", "value", distance);

		// set in ui
		$('#distance').val(distance);

		// save distance in cookie
		$.cookie('distance', distance, { expires: 7 });
	}
	// update shown venues
	get_venues_distance();
}
// shows an alert with the current location on a google map
// also the nearest venues are shown
function showLocation(el) {
	// current location
	var latlng = $('#lat').html() + "," + $('#lng').html();
	var img_url = "http://maps.googleapis.com/maps/api/staticmap?center="+latlng+"&zoom=15&language=de&size=400x300&sensor=false"+
	"&markers=color:red|"+latlng;

	// marker for each venue
	var marker = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	var cnt = 0;
	var key = "";
	$.each($('[class="venueDiv"]:visible'), function() {
		var latVenue = $(this).children('.lat').html();
		var lngVenue = $(this).children('.lng').html();
		var title = $(this).children('[class="title"]').children('a').html()

		img_url += "&markers=color:red|label:" + marker[cnt] + "|" + latVenue + "," + lngVenue;
		if (cnt < 5)
			key += marker[cnt] + ": " + title + "<br />";
		cnt++;
		if (cnt >= marker.length)
			cnt = 0;
	});

	// show in alert
	var data = '<img width="400" height="300" src="' + img_url + '"></img>';
	data += '<br />' + '<div class="locationMapLegend" style="">' + key + '</div>';
	alert(data, $('#location').html(), false, 425);

	// close shown tooltip
	$(el).tooltip("close");
}
function get_venues_distance() {
	// current location
	var lat = $('#lat').html();
	var lng = $('#lng').html();
	var distance = $('#distance').val();

	// for each venue
	var marker = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	var cnt = 0;
	$.each($('[class="venueDiv"]'), function() {
		var latVenue = $(this).children('.lat').html();
		var lngVenue = $(this).children('.lng').html();
		var obj = $(this);

		// get distance on clientside via JS
		var distanceString = "";
		var distanceValue = distanceLatLng(lat, lng, latVenue, lngVenue);
		var distanceMetersRound = Math.floor(Number((distanceValue).toFixed(2)) * 1000);

		if (distanceValue >= 1)
			distanceString = "~ " + distanceValue.toFixed(1) + " km";
		else
			distanceString = "~ " + distanceMetersRound + " m";

		// hide too far locations
		if (distanceMetersRound > distance)
			$(this).hide();
		else
			$(this).show();

		// remove old distance object if existing
		obj.children('[name="distance"]').remove();
		// create new distance object
		obj.append("<div name='distance'>Distanz: " + distanceString + "</div>");

	});
	// notifier if no venues found
	if ($('[class="venueDiv"]:visible').length < 1)
		$('#noVenueFoundNotifier').show();
	else
		$('#noVenueFoundNotifier').hide();
}

function setNoteDialog() {
	// show dialog
	$('#setNoteDialog').dialog({
		modal: true,
		title: "Notiz erstellen",
		buttons: {
			"Ok": function() {
				var note = $('#noteInput').val();
				vote_set_note(note);
				$('#setNoteDialog').dialog("close");
				$(this).dialog("close");
			}
		},
		width: "400"
	});
}

function handle_href_reference_details(id, reference, name) {
	$.ajax({
		type: 'POST',
		url:  'nearplaces.php',
		data: {
			'action'    : 'details',
			'id'        : id,
			'reference' : reference,
			'sensor'    : isMobileDevice()
		},
		dataType: 'json',
		async: false,
		success: function(result) {
			if (typeof result.alert != 'undefined')
				alert(result.alert);

			// got website via details api
			if (typeof result.result.website != 'undefined')
				window.open(result.result.website, '_blank');
			// now website open google search
			else
				window.open('https://www.google.com/#q=' + name, '_blank');
		},
		error: function() {
			alert('Fehler beim Abholen der Restaurants in der Nähe.');
		}
	});
}

function get_alt_venues(lat, lng, radius, results_old, success_function) {
	// if >= 10 venues found in step before in call succes_function immediately with empty new result
	if (results_old.length >= 10)
		return success_function(new Array());
	// < 10 found in step before, do current query
	$.ajax({
		type: 'POST',
		url:  'nearplaces.php',
		data: {
			'action' : 'nearbysearch_full',
			'lat'    : lat,
			'lng'    : lng,
			'radius' : radius,
			'sensor' : isMobileDevice()
		},
		dataType: "json",
		success: function(result) {
			if (typeof result.alert != 'undefined')
				alert(result.alert);
			else
				return success_function(result);
		},
		error: function() {
			alert('Fehler beim Abholen der Restaurants in der Nähe.');
		}
	});
}

function init_venues_alt() {
	var lat = $('#lat').html();
	var lng = $('#lng').html();

	$('#table_voting_alt').hide();
	$('#div_voting_alt_loader').show();

	// first step: get venues in 645 m radius
	var results = new Array();
	get_alt_venues(lat, lng, 645, results, function (results_near) {
		results = results.concat(results_near);
		// second step: get venues in set user radius (default 5000 m)
		get_alt_venues(lat, lng, $('#distance').val(), results_near, function (results_far) {
			results = results.concat(results_far);

			// prepare data for table
			data = new Array();
			$(results).each(function(index, element) {
				var distanceValue = distanceLatLng(lat, lng, element.lat, element.lng);
				var distanceMetersRound = Math.floor(Number((distanceValue).toFixed(2)) * 1000);
				var rating = '-';
				if (element.rating)
					rating = element.rating;

				var action_data = "<a href='" + element.maps_href + "' target='_blank'><span class='icon sprite sprite-icon_pin_map' title='Google Maps Route'></span></a>";
				if ($('#show_voting').length) {
					action_data += "<a href='javascript:void(0)' onclick='vote_up(\"" + element.name + "\")'><span class='icon sprite sprite-icon_hand_pro' title='Vote Up'></span></a>\
						<a href='javascript:void(0)' onclick='vote_down(\"" + element.name + "\")'><span class='icon sprite sprite-icon_hand_contra' title='Vote Down'></span></a>";
				}

				data[index] = new Array(
					element.href,
					distanceMetersRound,
					rating,
					action_data
				);
			});

			var dataTable = $('#table_voting_alt').dataTable({
				'aaData' : data,
				'bSort': true,
				/* make table replacable */
				"bDestroy": true,
				/* sort after distance, then after rating */
				"aaSorting": [ [ 1, 'asc' ], [2, 'desc'] ],
				'bLengthChange': false,
				/* number of rows on one page */
				'iDisplayLength': 8,
				/* no page x from y info and so on */
				'bInfo' : false,
				'aoColumns': [
					{"sTitle": "Name"},
					{"sTitle": "Distanz [m]", "sClass":" center"},
					{"sTitle": "Rating", "sClass":" center"},
					{"sTitle": "Aktionen", "sClass":" center"}
				],
				"oLanguage": {
					"sZeroRecords": "Leider nichts gefunden :(",
					"sSearch": "Filter:",
					"oPaginate": {
						"sPrevious": "Vorherige Seite",
						"sNext": "Nächste Seite"
					}
				}
			});
			$('#div_voting_alt_loader').hide();
			$('#table_voting_alt').show();
			if (dataTable.length > 0)
				dataTable.fnAdjustColumnSizing();
			$("#setAlternativeVenuesDialog").dialog("option", "position", "center");
		});
	});
}

function setAlternativeVenuesDialog() {
	init_venues_alt();

	// show dialog
	$('#setAlternativeVenuesDialog').dialog({
		modal: true,
		title: "Lokale in der Nähe",
		buttons: {
			"Schließen": function() {
				$(this).dialog("close");
			}
		},
		width: '650'
	});
}
// updates the gui on user changes
function updateVoteSettingsDialog() {
	// disable reminder checkbox if "send mail only if already voted" checkbox is checked
	$('#vote_reminder').attr('disabled', $('#voted_mail_only').is(':checked'));
}
function setVoteSettingsDialog() {
	// show dialog
	$('#setVoteSettingsDialog').dialog({
		modal: true,
		title: "Spezial-Votes & Einstellungen",
		buttons: {
			"Speichern / Schließen": function() {
				$.ajax({
					type: 'POST',
					url: 'emails.php',
					data: {
						'action': 'email_config_set',
						'email': $('#email').val(),
						'vote_reminder': $('#vote_reminder').is(':checked'),
						'voted_mail_only': $('#voted_mail_only').is(':checked')
					},
					dataType: "json",
					success: function(result) {
						if (typeof result.alert != 'undefined')
							alert(result.alert);
					},
					error: function() {
						alert('Fehler beim Setzen der Vote-Einstellungen.');
					}
				});
				$(this).dialog("close");
			}
		},
		width: '440'
	});
}

// INIT
$(document).ready(function() {
	// overwrite tooltip methode to use
	// html tooltips
	$.widget("ui.tooltip", $.ui.tooltip, {
		options: {
			content: function () {
				return $(this).prop('title');
			}
		}
	});

	// location ready event
	var locationReadyFired = false;
	$(document).on('locationReady', function() {
		locationReadyFired = true;
		// add distance user-venue to each venue
		// also hides venues which are too far away
		head.ready('cookie', function() {
			var distance = $.cookie('distance');

			// default distance
			if (typeof distance == 'undefined')
				distance = 5000;

			// init distance slider
			$("#sliderDistance").slider({
				value: distance,
				min: 100,
				max: 10000,
				step: 100,
				slide: function(event, ui) {
					setDistance(ui.value);
				}
			});

			// show social shares
			$('#socialShare').show();

			$('#loadingContainer').hide();
			setDistance(distance);
		});

		// replace @@lat_lng@@ placeholder in google maps hrefs
		$('[name="lat_lng_link"]').each(function(index, value) {
			var href = $(this).prop('href');
			href = href.replace('@@lat_lng@@', $('#lat').html() + ',' + $('#lng').html());
			$(this).prop('href', href);
		});

		// enable nice tooltips for some tags
		$('a').tooltip();
		$('span').tooltip();
		$('div').tooltip();
		$('input').tooltip();
	});

	// start location stuff
	head.ready('cookie', function() {
		var location = $.cookie('location');
		// custom location from cookie
		if (typeof location != 'undefined' && location && location.length) {
			setLocation(location, false);
		}
		// location via geolocation
		else {
			setLocation(null, false);
		}

		// show overlay info if not already shown
		// uses cookie to save/compare version info
		if ($('#overlay_info_version').length) {
			var overlay_info_version = $.trim($('#overlay_info_version').html());
			var overlay_info_version_cookie = $.trim($.cookie('overlay_info_version'));
			if (overlay_info_version != overlay_info_version_cookie) {
				$('#overlay_info').show("slide", {direction: "up"});
				// hide after 10 seconds, save version to cookie
				// to not show again
				setTimeout(function() {
					$.cookie('overlay_info_version', overlay_info_version, { expires: 7 });
					$('#overlay_info').hide("slide", {direction: "up"});
				}, 10000);
			}
		}
	});
	// fallback with timer if location ready event not fired
	setTimeout(function() {
		if (!locationReadyFired)
			$(document).trigger('locationReady');
	}, 10000);

	// show voting
	if ($('#show_voting').length)
		vote_get();

	// date picker
	$("#datePicker").datepicker({
		dateFormat: "yy-mm-dd",
		minDate: new Date(2012, 10, 30),
		maxDate: "1M",
		onSelect: function(dateText, inst) {
			document.location = window.location.protocol + "//" +
				window.location.host + window.location.pathname +
				"?date=" + dateText;
		}
	});

	// connect distance input with distance slider
	$('#distance').on('input change', function() {
		setDistance($(this).val());
	});

	// show datePicker when clicking on date header
	$('#dateHeader').click(function(event) {
		// close shown tooltip
		$("#dateHeader").tooltip("close");
		// show datepicker
		$("#datePicker").datepicker("show");
		// set current selected date
		$("#datePicker").datepicker("setDate", $('#date_GET').val());
	});

	// set submit handler for location input form
	$('#locationForm').submit(function(event) {
		var location = $('#locationInput').val();
		setLocation(location, false);
		$('#setLocationDialog').dialog("close");
		event.preventDefault();
	});

	// set submit handler for note input form
	$('#noteForm').submit(function(event) {
		var note = $('#noteInput').val();
		vote_set_note(note);
		$('#setNoteDialog').dialog("close");
		event.preventDefault();
	});

	// set change handler for setVoteSettingsDialog
	$('#setVoteSettingsDialog').change(function() {
		updateVoteSettingsDialog();
	});
	updateVoteSettingsDialog();
});

// alert override
window.alert = function(message, alertTitle, showIcon, width) {
	if (typeof alertTitle == 'undefined')
		var alertTitle = 'Hinweis';
	if (typeof showIcon == 'undefined' || showIcon)
		message = '<table><tr><td><span class="ui-icon ui-icon-alert" style="margin-right: 5px"></span></td><td>' + message + '</td></tr></table>';
	if (typeof width == 'undefined')
		width = 300;

	$(document.createElement('div'))
		.attr({title: alertTitle, 'class': 'alert'})
		.html(message)
		.dialog({
			title: alertTitle,
			resizable: false,
			modal: true,
			width: width,
			buttons: {
				'OK': function() {
					$(this).dialog('close');
				}
			}
		});
};
