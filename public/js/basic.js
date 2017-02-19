var intervalVotes = false;
var usedGeolocation = false;
var oldVoteData = null;
var voting_over_interval_multiplier = 1;
var ajax_retry_time_max = 3000;
var ajax_retry_count_max = 10;
var location_max_delay = 10000;
var nearplace_radius_default = 500;
var nearplace_radius_max_default = 1000;

var SHOW_DETAILS_MIN_WIDTH = 800;

var width_device = (window.innerWidth > 0) ? window.innerWidth : screen.width;

function isMobileDevice() {
	return /Android|webOS|iPhone|iPad|iPod|BlackBerry|MIDP|Nokia|J2ME/i.test(navigator.userAgent);
}

function detectIE() {
	var ua = window.navigator.userAgent;
	var msie = ua.indexOf('MSIE ');
	var trident = ua.indexOf('Trident/');

	if (msie > 0) {
		// IE 10 or older => return version number
		return parseInt(ua.substring(msie + 5, ua.indexOf('.', msie)), 10);
	}

	if (trident > 0) {
		// IE 11 (or newer) => return version number
		var rv = ua.indexOf('rv:');
		return parseInt(ua.substring(rv + 3, ua.indexOf('.', rv)), 10);
	}

	// other browser
	return false;
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
function vote_helper(action, identifier, note, try_count) {
	// piwik track
	if (typeof _paq != 'undefined' && action != 'vote_get')
		_paq.push(['trackEvent', 'Voting', action, identifier, note]);
	// ajax call
	$.ajax({
		type: 'POST',
		url: '/vote.php',
		data: {
			'action': action,
			'identifier': identifier,
			'note': note,
			'date': $('#date').val(),
			'lat': $('#lat').html(),
			'lng': $('#lng').html(),
			'radius': nearplace_radius_default,
			'radius_max': nearplace_radius_max_default,
			'sensor': (typeof navigator.geolocation != 'undefined')
		},
		dataType: 'json',
		success: function(result) {

			// increase interval multiplier to reduce server load
			if (intervalVotes) {
				clearInterval(intervalVotes);
			}
			if (typeof result.voting_over != 'undefined' && result.voting_over || !result || typeof result.alert != 'undefined') {
				voting_over_interval_multiplier += 0.1;
			} else {
				voting_over_interval_multiplier = 1;
			}
			intervalVotes = setInterval(function(){vote_get()}, Math.floor(5000 * voting_over_interval_multiplier));

			// exit, if we got the same as before
			// except it is a server alert
			if (
				typeof JSON != 'undefined' &&
				JSON.stringify(oldVoteData) == JSON.stringify(result) &&
				typeof result.alert == 'undefined'
			) {
				return;
			}

			// alert from server (e.g. error)
			if (typeof result.alert != 'undefined') {
				alert(result.alert);
			// got valid vote result
			} else if (typeof result.html != 'undefined') {
				$("#dialog_ajax_data").html(result.html);
				/*if (width_device <= SHOW_DETAILS_MIN_WIDTH) {
					$('#button_vote_summary_toggle').show();
				} else {
					$("#dialog_vote_summary").css('display', 'table');
				}*/
			// no | empty result => hide voting dialog
			} else {
				if (intervalVotes) {
					$("#dialog_vote_summary").hide();
				}
			}
			oldVoteData = result;
			// handle emoji replaces
			emoji_update();
		},
		error: function() {
			// retry on error
			if (try_count < ajax_retry_count_max) {
				window.setTimeout(function() { vote_helper(action, identifier, note, try_count+1) }, (Math.random()*ajax_retry_time_max)+1);
			} else {
				alert('Fehler beim Abfragen/Setzen der Votes.');
			}
		}
	});
}
// vote up
function vote_up(identifier) {
	vote_helper('vote_up', identifier, null, 0);
	$('#dialog_vote_summary').show();
	adapt_button_vote_summary_toggle();
}
// vote down
function vote_down(identifier) {
	vote_helper('vote_down', identifier, null, 0);
	$('#dialog_vote_summary').show();
	adapt_button_vote_summary_toggle();
}
// vote special
function vote_special(identifier) {
	$('#noteInput').val(identifier);
	vote_helper('vote_special', identifier, null, 0);
	$('#dialog_vote_summary').show();
	adapt_button_vote_summary_toggle();
}
// set note
function vote_set_note(note) {
	vote_helper('vote_set_note', null, note, 0);
	$('#dialog_vote_summary').show();
	adapt_button_vote_summary_toggle();
}
// get votes
function vote_get() {
	vote_helper('vote_get', null, null, 0);
}
// delete vote
function vote_delete() {
	$('#noteInput').val('');
	vote_helper('vote_delete', null, null, 0);
}
// delete vote part
function vote_delete_part(identifier) {
	if (identifier == 'special') {
		$('#noteInput').val('');
	}
	vote_helper('vote_delete_part', identifier, null, 0);
}
// got (lat / long) location => get address from it
function positionHandler(position) {
	lat = position.coords.latitude;
	lng = position.coords.longitude;

	// get address via ajax
	$.ajax({
		type: 'GET',
		url: '/locator.php',
		data: { 'action': 'latlngToAddress', 'lat': lat, 'lng': lng },
		dataType: 'json',
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
	$.each($('.venueDiv'), function() {
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
		} else if (diffA < diffB) {
			return -1;
		} else {
			return 0;
		}
	});

	// load sorted venues back in venue container
	$('#venueContainer').html(venueSortArray);

	// locationReady event
	$(document).trigger('locationReady');
}

function setLocation(location, force_geolocation, try_count) {
	// location via geolocation
	if (!location || force_geolocation) {
		// use geolocation via client
		if ((isMobileDevice() || force_geolocation) && navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(positionHandler, positionErrorHandler, {timeout: 5000});
		} else {
			// sort venues
			sortVenuesAfterPosition($('#lat').html(), $('#lng').html());
		}
		$.removeCookie('location');
		return;
	}
	usedGeolocation = false;

	// get lat / lng via ajax
	$.ajax({
		type: 'GET',
		url: '/locator.php',
		data: { 'action': 'addressToLatLong', 'address': location },
		dataType: 'json',
		success: function(result) {

			if (result && typeof result.lat != 'undefined' && result.lat && typeof result.lng != 'undefined' && result.lng) {
				// custom location
				$('#location').html(location);
				$('#locationInput').val(location);

				// sort venues
				sortVenuesAfterPosition(result.lat, result.lng);

				// save custom location in cookie
				$.cookie('location', location, { expires: 7 });
			} else {
				$('#locationInput').val($('#location').html());
				alert('Keine Geo-Daten zu dieser Adresse gefunden.');
			}
		},
		error: function() {
			// retry
			if (try_count < ajax_retry_count_max) {
				window.setTimeout(function() { setLocation(location, force_geolocation, try_count+1); }, (Math.random()*ajax_retry_time_max)+1);
			} else {
				alert('Fehler beim Abrufen der Geo-Position. Bitte Internetverbindung überprüfen.');
			}
		}
	});
}
function setDistance(distance) {
	if (typeof distance != 'undefined') {
		// set in ui
		$('#distance').val(distance);
		// update slider
		if ($('#distance').hasClass('ui-slider-input'))
			$('#distance').slider('refresh');

		// save distance in cookie
		$.cookie('distance', distance, { expires: 7 });
	}

	// update shown venues
	// current location
	var lat = $('#lat').html();
	var lng = $('#lng').html();
	var distance = $('#distance').val();

	// for each venue
	var marker = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	var cnt = 0;
	$.each($('.venueDiv'), function() {
		var latVenue = $(this).children('.lat').html();
		var lngVenue = $(this).children('.lng').html();
		var obj = $(this);
		var title = $('.title', obj).text();

		// get distance on clientside via JS
		var distanceString = "";
		var distanceValue = distanceLatLng(lat, lng, latVenue, lngVenue);
		var distanceMetersRound = Math.floor(Number((distanceValue).toFixed(2)) * 1000);

		if (distanceValue >= 1) {
			distanceString = "~ " + distanceValue.toFixed(1) + " km";
		} else {
			distanceString = "~ " + distanceMetersRound + " m";
		}

		// hide too far locations
		if (distanceMetersRound > distance) {
			$(this).hide();
			$(this).attr('data-inreach', false);
		} else {
			if (!$(this).attr('data-userhidden')) {
				$(this).show();
			}
			$(this).attr('data-inreach', true);
		}

		// remove old distance object if existing
		obj.children('.distance').remove();
		// create new distance object
		obj.append("<div class='distance'>Distanz: " + distanceString + "</div>");

	});

	// update no venue found notifier
	if ($('[class="venueDiv"][data-inreach="true"]').length < 1) {
		$('#noVenueFoundNotifier').show();
	} else {
		$('#noVenueFoundNotifier').hide();
	}
}

function updateNotePreview() {
	$('#notePreview').html(emojione.toImage($("#noteInput").val()));
}

function handle_href_reference_details(id, reference, name, try_count) {
	$.ajax({
		type: 'GET',
		url: '/nearplaces.php',
		data: {
			'action': 'details',
			'id': id,
			'reference': reference,
			'sensor': (typeof navigator.geolocation != 'undefined')
		},
		dataType: 'json',
		async: false,
		success: function(result) {
			if (typeof result.alert != 'undefined') {
				alert(result.alert);
			}

			// got website via details api
			if (typeof result.result.website != 'undefined') {
				window.open(result.result.website, '_blank');
			// no website, open search
			} else {
				window.open($('#SEARCH_PROVIDER').html() + name, '_blank');
			}
		},
		error: function() {
			// retry
			if (try_count < ajax_retry_count_max) {
				window.setTimeout(function() { handle_href_reference_details(id, reference, name, try_count+1); }, (Math.random()*ajax_retry_time_max)+1);
			} else {
				alert('Fehler beim Abholen der Restaurants in der Nähe.');
			}
		}
	});
}

function get_alt_venues(lat, lng, radius, radius_max, success_function, try_count) {
	$.ajax({
		type: 'GET',
		url: '/nearplaces.php',
		data: {
			'action': 'nearbysearch_staged', // takes so long, is it worth it?
			//'action': 'nearbysearch_full',
			'lat': lat,
			'lng': lng,
			'radius': radius,
			'radius_max': radius_max,
			'sensor': (typeof navigator.geolocation != 'undefined')
		},
		dataType: 'json',
		success: function(result) {
			if (typeof result.alert != 'undefined') {
				alert(result.alert);
			} else {
				return success_function(result);
			}
		},
		error: function() {
			if (try_count < ajax_retry_count_max) {
				window.setTimeout(function() { get_alt_venues(lat, lng, radius, radius_max, success_function, try_count+1); }, (Math.random()*ajax_retry_time_max)+1);
			} else {
				alert('Fehler beim Abholen der Restaurants in der Nähe.');
			}
		}
	});
}

function vote_settings_save() {
	$.ajax({
		type: 'POST',
		url: '/users.php',
		data: {
			'action': 'user_config_set',
			'name': $('#name').val(),
			'email': $('#email').val(),
			'vote_reminder': $('#vote_reminder').is(':checked'),
			'voted_mail_only': $('#voted_mail_only').is(':checked'),
			'vote_always_show': $('#vote_always_show').is(':checked')
		},
		dataType: "json",
		success: function(result) {
			if (typeof result.alert != 'undefined') {
				alert(result.alert);
			}
		},
		error: function() {
			alert('Fehler beim Setzen der Vote-Einstellungen.');
		}
	});
}

// handle missing emoji replaces
function emoji_update() {
	if (typeof emojione == 'undefined') {
		return;
	}
	$('.convert-emoji').not('[data-emoji-converted]').each(function() {
		$(this).attr('data-emoji-converted', true);
		$(this).html(emojione.toImage($(this).html()));
	});
}

function adapt_button_vote_summary_toggle() {
	if ($('#dialog_vote_summary').is(':visible')) {
		$('#button_vote_summary_toggle').val('Voting Ausblenden').html('Voting Ausblenden');
	} else {
		$('#button_vote_summary_toggle').val('Voting Anzeigen').html('Voting Anzeigen');
	}
}

function venue_hide(id) {
	// save custom location in cookie
	var venues_hidden = $.cookie('venues_hidden');
	if (typeof venues_hidden != 'object') {
		venues_hidden = new Array();
	}

	venues_hidden.push(id);

	// save hidden venue in cookie for 1 month
	$.cookie('venues_hidden', venues_hidden, { expires: 31 });

	// hide venue in gui immediately
	$('#' + id).hide();
}

function init_cookie() {
	$(document).ready(function() {
		$.cookie.json = true; // we want to store json object for e.g. venue hiding

		// hide user hidden venues
		$.each($.cookie('venues_hidden'), function (index, value) {
			$('#' + value).hide();
			$('#' + value).attr('data-userhidden', true);
		});

		// do location inits
		var location = $.cookie('location');
		// custom location from cookie
		if (typeof location != 'undefined' && location && location.length) {
			setLocation(location, false, 0);
		// location via geolocation
		} else {
			setLocation(null, false, 0);
		}
	});
}

function init_emoji() {
	$(document).ready(function() {
		// default options
		emojione.ascii = true;
		emojione.imageType = 'png';
		//emojione.imagePathSVGSprites = 'emojione/sprites/emojione.sprites.svg';
		emojione.sprites = true;

		// handly emoji replaces
		emoji_update();

		// stop inits if input not found
		if (!$("#noteInput").length) {
			return;
		}

		// note input handles
		$("#noteInput").on('keyup change input',function(e) {
			updateNotePreview();
		});
		updateNotePreview();

		// emoji shortname textcomplete for note input
		$.getJSON('emojione/emoji.json?2', function (emojiStrategy) {
			// append custom keywords
			emojiStrategy.toilet.keywords.push('heisl');
			emojiStrategy.spaghetti.keywords.push('pasta');

			// init input
			$("#noteInput").textcomplete([ {
				match: /\B:([\-+\w]*)$/,
				search: function (term, callback) {
					var results = [];
					var results2 = [];
					var results3 = [];
					$.each(emojiStrategy,function(shortname,data) {
						if(shortname.indexOf(term) > -1) { results.push(shortname); }
						else {
							if((data.aliases !== null) && (data.aliases.indexOf(term) > -1)) {
								results2.push(shortname);
							}
							else if((data.keywords !== null) && (data.keywords.indexOf(term) > -1)) {
								results3.push(shortname);
							}
						}
					});

					if(term.length >= 3) {
						results.sort(function(a,b) { return (a.length > b.length); });
						results2.sort(function(a,b) { return (a.length > b.length); });
						results3.sort();
					}
					var newResults = results.concat(results2).concat(results3);

					callback(newResults);
				},
				template: function (shortname) {
					return '<img class="emojione" src="./emojione/png/'+emojiStrategy[shortname].unicode+'.png"> :'+shortname+':';
					//return ' :'+shortname+':';
				},
				replace: function (shortname) {
					return ':'+shortname+': ';
				},
				index: 1,
				maxCount: 10
			}
			],{
				footer: '<a href="http://www.emoji.codes" target="_blank">Alle Anzeigen<span class="arrow">»</span></a>'
			});
		});
	});
}

function init_datatables() {
	$(document).ready(function() {
		$('#table_stats').dataTable({
			'order': [[ 4, 'desc' ]],
			'dom': '<lfpti>',
			'lengthChange': false,
			'searching': true,
			'fnDrawCallback': function (oSettings) {
				/* change input type of the search field because of jquerymobile */
				$('#table_stats_filter input').attr('type', 'text')
						.attr('data-type', 'search');
				$('#table_stats_filter input').textinput();
				emoji_update();
			}
		});
		$('#table_stats').show();
		$('#table_ingredients').dataTable({
			'order': [[ 2, 'desc' ]],
			'dom': '<lfpti>',
			'lengthChange': false,
			'searching': false
		});
		$('#table_ingredients').show();
		$('#table_compositions').dataTable({
			'order': [[ 2, 'desc' ]],
			'dom': '<lfpti>',
			'lengthChange': false,
			'searching': false
		});
		$('#table_compositions').show();

		$('#loader_stats').hide();
	});
};

// handle init
$(document).ready(function() {
	// hide location info on small screens
	if (width_device <= SHOW_DETAILS_MIN_WIDTH) {
		$('#location').hide();
	}

	// old ie warning (not supported by jquery 2.*)
	var ie_version = detectIE();
	if (ie_version && ie_version <= 8) {
		alert('Bitte neueren Internet Explorer verwenden!');
	}

	// show voting
	if ($('#show_voting').length) {
		vote_get();
	}

	// date change handler
	$('#date').bind('change', function() {
		document.location = window.location.protocol + "//" + window.location.host + window.location.pathname + "?date=" + $(this).val();
	});

	// set submit handler for location input form
	$('#locationForm').submit(function(event) {
		event.preventDefault();
		$('#setLocationDialog').dialog('close');
		setDistance($('#distance').val());
		setLocation($('#locationInput').val(), false, 0);
	});

	// set submit handler for note input form
	$('#noteForm').submit(function(event) {
		event.preventDefault();
		$('#setNoteDialog').dialog('close');
		vote_set_note($('#noteInput').val());
	});

	// button_vote_summary_toggle click handler
	$('#button_vote_summary_toggle').click(function() {
		var el_dialog_vote_summary = $('#dialog_vote_summary');
		if (el_dialog_vote_summary.is(':visible')) {
			el_dialog_vote_summary.hide();
		} else {
			el_dialog_vote_summary.show();
		}
		adapt_button_vote_summary_toggle();
	});
	if ($('#vote_always_show').is(':checked')) {
		$('#dialog_vote_summary').show();
		setTimeout(function() {
			adapt_button_vote_summary_toggle();
		}, 0);
	}

	// tab activate handles
	$('#tabs').on('tabsactivate', function(event, ui) {
		// alternative venues
		if (ui.newTab.index() == 1) {
			var lat = $('#lat').html();
			var lng = $('#lng').html();

			$('#table_voting_alt').hide();
			$('#div_voting_alt_loader').show();

			// get venues in default defined distance radius
			var results = new Array();
			get_alt_venues(lat, lng, nearplace_radius_default, nearplace_radius_max_default, function (results) {
				// destroy old table
				if ($.fn.DataTable.isDataTable('#table_voting_alt'))
					$('#table_voting_alt').dataTable().fnDestroy();
				$('#table_voting_alt').dataTable({
					'data': results,
					'columns': [
						{ 'title': 'Name' },
						{ 'title': 'Distanz' },
						//{ 'title': 'Rating' },
						{ 'title': 'Aktionen' }
					],
					'order': [[ 1, 'asc' ]],
					'initComplete': function(settings, json) {
						// style select with jquery mobile selectmenu
						$('#table_voting_alt_wrapper select').selectmenu();
						$('#table_voting_alt_wrapper input').textinput();
					},
					'fnDrawCallback': function (oSettings) {
						// change input type of the search field because of jquerymobile
						$('#table_voting_alt_filter input').attr('type', 'text')
								.attr('data-type', 'search');
						$('#table_voting_alt_filter input').textinput();
						// update iconized button links
						$('#table_voting_alt_wrapper div.ui-link').button({
							enhanced: true
						});
					},
					'dom': '<lfpti>',
					'lengthChange': false,
					'searching': true
				});
				$('#div_voting_alt_loader').hide();
				$('#table_voting_alt').show();
			}, 0);
		}
	});
});

// location ready event
var locationReadyFired = false;
$(document).on('locationReady', function() {
	$(document).ready(function() {
		locationReadyFired = true;
		// add distance user-venue to each venue
		// also hides venues which are too far away
		var distance = $.cookie('distance');

		// default distance
		if (typeof distance == 'undefined') {
			distance = $('#distance_default').html();
		}

		setDistance(distance);

		$('#loadingContainer').hide();

		// replace @@lat_lng@@ placeholder in google maps hrefs
		$('.lat_lng_link').each(function(index, value) {
			var href = $(this).prop('href');
			href = href.replace('@@lat_lng@@', $('#lat').html() + ',' + $('#lng').html());
			$(this).prop('href', href);
		});
	});
});

// fallback with timer if location ready event not fired
setTimeout(function() {
	if (!locationReadyFired) {
		$(document).trigger('locationReady');
	}
}, location_max_delay);
