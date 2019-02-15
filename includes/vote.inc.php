<?php

require_once(__DIR__ . '/venues.php');
require_once(__DIR__ . '/nearplaces.inc.php');
require_once(__DIR__ . '/VoteHandler_MySql.php');

define('VOTE_NOTE_MAX_LENGTH', 128);
define('VOTE_DATE_FORMAT', 'Y-m-d');

$votes_valid_special = [ 'Verweigerung', 'Egal', 'special' ];

function returnVotes($votes) {
	global $voting_over_time;

	if (is_array($votes) && !empty($votes)) {
		echo json_encode([
			'voting_over' => (time() >= $voting_over_time),
			'html' => vote_summary_html($votes),
		]);
	// no voting data
	} else {
		echo json_encode([
			'voting_over' => (time() >= $voting_over_time),
			'html' => vote_summary_html($votes),
		]);
	}
}

function vote_allowed() {
	global $timestamp, $voting_over_time;
	return (
		is_intern_ip() &&
		date('Ymd') < date('Ymd', $timestamp) ||
		(date('Ymd') == date('Ymd', $timestamp) && time() < $voting_over_time)
	);
}

function check_voting_time() {
	if (!vote_allowed()) {
		exit(json_encode([ 'alert' => "Das Voting ist bereits beendet!" ]));
	}
}

function getAllVotes($ip = null, $category = null) {
	global $timestamp;
	return VoteHandler_MySql::getInstance($timestamp)->get(date(VOTE_DATE_FORMAT, $timestamp), $ip, $category);
}

// tries to get the website from the venue class
// searches nearplace caches or the real venue entries
// and uses a simple google search as fallback
function getWebsiteFromVenueClass($venue_class) {
	if (class_exists($venue_class)) {
		$venueTmp = new $venue_class;
		return $venueTmp->url;
		$venue_title = '<a href="' . $venueTmp->url . '" target="_blank" title="Homepage" style="color: inherit ! important; font-weight: inherit ! important;">' . htmlspecialchars($venueTmp->title) . '</a>';
	} else {
		$website = nearby_venue_website($venue_class);
		// search provider fallback
		if ($website === null) {
			$website = SEARCH_PROVIDER . urlencode($venue_class);
		}
		return $website;
	}
}

function getTitleFromVenueClass($venue_class) {
	if (class_exists($venue_class)) {
		$venueTmp = new $venue_class;
		return $venueTmp->title;
	} else {
		return $venue_class;
	}
}

function votes_adapt($votes, $user, $show_js_actions = true, $style_custom = 'text-decoration: none ! important; color: inherit ! important; font-weight: inherit ! important;') {
	foreach ($votes as &$venue_class) {
		$website = getWebsiteFromVenueClass($venue_class);
		$title = htmlspecialchars(getTitleFromVenueClass($venue_class));
		// Dirty convert quotes to HTML entities in order avoid escaping problems in JS
		$venue_class = htmlspecialchars($venue_class, ENT_QUOTES);
		$venue_title = "<a href='{$website}' target='_blank' title='Homepage' style='{$style_custom}'>
				<span style='{$style_custom}'>{$title}</span>
			</a>";
		// current user => add delete functionality
		if ($show_js_actions && $user == get_ip()) {
			$venue_title .= " <sup title='Löschen'><a href='javascript:void(0)' onclick='vote_delete_part(\"{$venue_class}\")' title='' style='color: red ! important'>x</a></sup>";
		// otherwise => add vote actions
		} else if ($show_js_actions) {
			$venue_title .= ' ';
			$venue_title .= "<sup title='Vote Up'><a href='javascript:void(0)' onclick='vote_up(\"{$venue_class}\")' style='color: red ! important'>+1</a></sup>";
			$venue_title .= '<sup> | </sup>';
			$venue_title .= " <sup title='Vote Down'><a href='javascript:void(0)' onclick='vote_down(\"{$venue_class}\")' style='color: red ! important'>-1</a></sup>";
		}
		$venue_class = trim($venue_title);
	}
	unset($venue_class);
	return $votes;
}

function vote_get_rankings($votes, $preserve_only_top=3) {
	// get venue ratings
	$venue_rating = [];

	foreach ($votes as $user => $vote_data) {
		foreach ($vote_data['votes'] as $venue => $vote) {
			if (is_array($vote)) {
				foreach ($vote as $vote_part) {
					if (!isset($venue_rating[$venue])) {
						$venue_rating[$venue] = ($vote_part == 'up') ? 1 : -1;
					} else {
						$venue_rating[$venue] += ($vote_part == 'up') ? 1 : -1;
					}
				}
			} else if ($venue != 'special') {
				if (!isset($venue_rating[$venue])) {
					$venue_rating[$venue] = ($vote == 'up') ? 1 : -1;
				} else {
					$venue_rating[$venue] += ($vote == 'up') ? 1 : -1;
				}
			}
		}
	}

	// remove <= 0 rankings
	foreach ($venue_rating as $key => $value) {
		if ($value <= 0) {
			unset($venue_rating[$key]);
		}
	}

	$ratings = array_values($venue_rating);
	rsort($ratings);

	// build new grouped by rating list
	$venue_rating_final = [];
	foreach ($ratings as $rating) {
		$venue_rating_final[$rating] = array_keys($venue_rating, $rating);
	}

	// only preserve top x for rating
	if ($preserve_only_top) {
		$venue_rating_final = array_slice($venue_rating_final, 0, $preserve_only_top, true);
	}

	return $venue_rating_final;
}

function ranking_summary_html($rankings, $title, $display_menus=false, $show_js_actions=true, $style_custom='text-decoration: none ! important; color: inherit ! important; font-weight: inherit ! important;') {
	$html = '<table style="border-spacing: 5px">';
	$html .= "<tr><td><b>${title}</b></td></tr>";
	$cnt = 1;
	// get all sane rating count
	$all_sane_rating_cnt = 0;
	foreach ($rankings as $rating => $venues) {
		$all_sane_rating_cnt += $rating;
	}
	foreach ($rankings as $rating => $venues) {
		sort($venues);
		// resolve class names to venue titles
		foreach ($venues as &$venue_class) {
			$website = getWebsiteFromVenueClass($venue_class);
			$title = htmlspecialchars(getTitleFromVenueClass($venue_class));
			$venue_class = "<a href='{$website}' target='_blank' title='Homepage' style='{$style_custom}'>
					<span style='{$style_custom}'>{$title}</span>
				</a>";
		}
		unset($venue_class);

		// mark venues which got >= 50% sane ratings
		// but only if there are multiple venues
		if (count($rankings) > 1 && ($rating / $all_sane_rating_cnt) >= 0.5) {
			$html .= "<tr><td title='Votes >= 50%' style='font-weight: bold;'>$cnt. " . implode(', ', $venues) . " [$rating]</td></tr>";
		} else {
			$html .= "<tr><td>$cnt. " . implode(', ', $venues) . " [$rating]</td></tr>";
		}
		$cnt++;
	}
	if (empty($rankings)) {
		$html .= '<tr><td>Kein Ergebnis</td></tr>';
	}
	$html .= '</table>';

	// print menu of rated venues (for email notifier)
	if ($display_menus) {
		$html_menu = '';
		foreach ((array)$rankings as $venues) {
			foreach ((array)$venues as $venue_class) {
				//error_log($venue_class);
				if (class_exists($venue_class)) {
					$venueTmp = new $venue_class;
					//error_log($venueTmp->getMenuData());
					//error_log($venueTmp->title);
					$html_menu .= "<div style='margin: 10px 5px'><span style='font-weight: bold'>{$venueTmp->title}</span>{$venueTmp->getMenuData()}</div>";
				}
			}
		}
		if (!empty($html_menu)) {
			$html .= '<br />';
			$html .= '<div style="margin: 5px; font-weight: bold">Menüs:</div>';
			$html .= $html_menu;
		}
	}
	return $html;
}

function vote_summary_html($votes, $display_menus = false, $show_js_actions = true) {
	global $timestamp, $votes_valid_special;
	$html = '';

	// no votes yet
	if (!is_array($votes) || empty($votes)) {
		return "<div style='margin: 5px'>Noch keine Daten vorhanden</div>";
	}

	// minimal => no vote actions
	if (isset($_GET['minimal'])) {
		$show_js_actions = false;
	}

	// table with details
	// note: use inline style here for email
	$html .= '<table style="border-spacing: 5px" data-role="table">
		<thead>
			<tr>
				<th style="text-align: center"><b>Benutzer</b></th>
				<th style="text-align: center"><b>Vote Ups</b></th>
				<th style="text-align: center"><b>Vote Downs</b></th>
				<th style="text-align: center"><b>Notizen</b></th>
			</tr>
		</thead><tbody>';
	foreach ($votes as $user_ip => $vote_data) {
		$upVotes = array_keys($vote_data['votes'], 'up');
		$downVotes = array_keys($vote_data['votes'], 'down');
		$specialVote = isset($vote_data['votes']['special']) ? $vote_data['votes']['special'] : null;

		sort($upVotes);
		sort($downVotes);

		// style adaptions according to vote
		if ($specialVote === $votes_valid_special[0]) {
			$row_style = 'color: #f99';
		} elseif ($specialVote === $votes_valid_special[1]) {
			$row_style = 'color: #999';
		} else {
			$row_style = '';
		}

		$upVotes_style = empty($upVotes) ? 'text-decoration: none ! important; color: #999; text-align: center;' : 'text-decoration: none ! important; color: #008000;';
		$downVotes_style = empty($downVotes) ? 'text-decoration: none ! important; color: #999; text-align: center;' : 'text-decoration: none ! important; color: #FF0000;';
		$specialVote_style = empty($specialVote) ? 'text-decoration: none ! important; color: #999; text-align: center;' : 'text-decoration: none ! important; text-align: center;';

		$upVotes = votes_adapt($upVotes, $user_ip, $show_js_actions, $upVotes_style);
		$downVotes = votes_adapt($downVotes, $user_ip, $show_js_actions, $downVotes_style);

		// cleanup other data for output
		$specialVote = htmlspecialchars($specialVote);
		// replace urls with an a tag
		$specialVote = preg_replace('/(https?:\/\/[^\s]+)/', '<a href="$1" target="_blank">$1</a>', $specialVote);
		// current user => add delete functionality
		if ($show_js_actions && $user_ip == get_ip()) {
			if (!empty($specialVote)) {
				$specialVote .= ' <sup title="Löschen"><a href="javascript:void(0)" onclick="vote_delete_part(\'special\')" style="color: red ! important">x</a></sup>';
			} else {
				$specialVote = '<a href="#setNoteDialog" data-rel="dialog" data-transition="pop" class="ui-link">Notiz</a>';
			}
		// otherwise => add "me too" functionality
		} else if ($show_js_actions && !empty($specialVote) && in_array($specialVote, $votes_valid_special)) {
			$specialVote .= " <sup title='Selbiges voten'><a href='javascript:void(0)' onclick='vote_special(\"{$specialVote}\")' style='color: red ! important'>+1</a></sup>";
		}

		// prepare data for output
		$upVotes = empty($upVotes) ? '-' : implode(', ', $upVotes);
		$downVotes = empty($downVotes) ? '-' : implode(', ', $downVotes);
		$specialVote = empty($specialVote) ? '-' : $specialVote;

		$html .= "<tr style='$row_style'>
			<td>" . htmlspecialchars($vote_data['name']) . "</td>
			<td style='$upVotes_style'>{$upVotes}</td>
			<td style='$downVotes_style'>{$downVotes}</td>
			<td style='$specialVote_style'>{$specialVote}</td>
		</tr>";
	}
	$html .= '</tbody></table>';

	// current ranking
	$html .= '<table><tbody><tr>';
	$html .= '<td style="vertical-align: top">';
	$html .= ranking_summary_html(
		vote_get_rankings($votes),
		'Tages-Ranking:',
		$display_menus,
		$show_js_actions
	);
	$html .= '</td>';
	// weekly ranking
	$html .= '<td style="vertical-align: top">';
	$html .= ranking_summary_html(
		vote_get_rankings(
			VoteHandler_MySql::getInstance($timestamp)->get_weekly(
				date('W', $timestamp),
				date('Y', $timestamp)
			)
		),
		'Wochen-Ranking:',
		false,
		$show_js_actions
	);
	$html .= '</td>';
	$html .= '</tr></tbody></table>';

	return html_compress($html);
}
