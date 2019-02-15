var intervalVotes = false;
var oldVoteData = null;
var voting_over_interval_multiplier = 1;

var SHOW_DETAILS_MIN_WIDTH = 800;

var width_device = (window.innerWidth > 0) ? window.innerWidth : screen.width;

// sends vote action (vote_up, vote_down, vote_get) and identifier (delete, restaurant name, ..) to server
function vote_helper(action, identifier, note) {
	// ajax call
	$.ajax({
		type: 'POST',
		url: '/vote.php',
		data: {
			'action': action,
			'identifier': identifier,
			'note': note,
			'date': $('#date').val()
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
			// no | empty result => hide voting dialog
			} else {
				if (intervalVotes) {
					$("#dialog_vote_summary").hide();
				}
			}
			oldVoteData = result;
		},
		error: function() {
			alert('Fehler beim Abfragen/Setzen der Votes.');
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

function get_alt_venues(success_function) {
	$.ajax({
		type: 'GET',
		url: '/nearplaces.php',
		data: {
			'action': 'nearbysearch',
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
			alert('Fehler beim Abholen der Restaurants in der Nähe.');
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
	$('#loadingContainer').hide();

	// show voting
	if ($('#show_voting').length) {
		vote_get();
	}

	// date change handler
	$('#date').bind('change', function() {
		document.location = window.location.protocol + "//" + window.location.host + window.location.pathname + "?date=" + $(this).val();
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
			$('#table_voting_alt').hide();
			$('#div_voting_alt_loader').show();

			// get venues
			var results = new Array();
			get_alt_venues(function (results) {
				// destroy old table
				if ($.fn.DataTable.isDataTable('#table_voting_alt'))
					$('#table_voting_alt').dataTable().fnDestroy();
				$('#table_voting_alt').dataTable({
					'data': results,
					'columns': [
						{ 'title': 'Name' },
						{ 'title': 'Distanz' },
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
