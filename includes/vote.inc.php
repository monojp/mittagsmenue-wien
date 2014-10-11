<?php

require_once(__DIR__ . '/venues.php');
require_once(__DIR__ . '/nearplaces.inc.php');

define('VOTE_FILE', TMP_PATH . 'votes.json');
define('VOTE_NOTE_MAX_LENGTH', 128);

function ip_username_sort($a, $b) {
	$a_real = ip_anonymize($a);
	$b_real = ip_anonymize($b);
	if ($a_real == $b_real)
		return 0;
	return ($a_real < $b_real) ? -1 : 1;
}

function returnVotes($votes) {
	global $voting_over_time;
	$html_return = (get_var('html') !== null) | (get_var('html/') !== null);

	if (isset($votes['venue']) && !empty($votes['venue'])) {
		if (is_array($votes['venue']))
			uksort($votes['venue'], 'ip_username_sort');

		if ($html_return) {
			require_once('guihelper.php');
			require_once('header.php');
			if (show_voting() && isset($_GET['minimal'])) {
				echo '<meta http-equiv="refresh" content="10" />';
				echo get_minimal_site_notice_html();
				echo '<div id="dialog_vote_summary" style="display: table">' . vote_summary_html($votes) . '</div>';
			}
			else if (show_voting()) {
				echo '<div style="display: none" id="show_voting"></div>';
				echo get_vote_div_html();
				echo get_noscript_html();
			}
			//echo vote_summary_html($votes);
			require_once('footer.php');
		}
		else
			echo json_encode(array(
				'voting_over' => (time() >= $voting_over_time),
				'html'        => vote_summary_html($votes),
			));
	}
	// no voting data
	else {
		if ($html_return) {
			require_once('guihelper.php');
			require_once('header.php');
			if (show_voting() && isset($_GET['minimal'])) {
				echo '<meta http-equiv="refresh" content="10" />';
				echo get_minimal_site_notice_html();
				echo '<div id="dialog_vote_summary" style="display: table">' . vote_summary_html($votes) . '</div>';
			}
			else if (show_voting()) {
				echo '<div style="display: none" id="show_voting"></div>';
				echo get_vote_div_html();
				echo get_noscript_html();
			}
			require_once('footer.php');
		}
		else
			echo json_encode(array(
				'voting_over' => (time() >= $voting_over_time),
				'html'        => vote_summary_html($votes),
			));
	}
}

function saveReturnVotes($votes) {
	global $voting_over_time;

	// save timestamp to delete old votings
	$votes['access'] = time();

	// save votes to file
	if (file_put_contents(VOTE_FILE, json_encode($votes)) === FALSE)
		echo json_encode(array('alert' => "Das Voting konnte nicht gesetzt werden!"));
	// sort and return votes
	returnVotes($votes);
}

function check_voting_time() {
	global $voting_over_time;

	if (time() >= $voting_over_time) {
		$voting_over_time_print = date('H:i', $voting_over_time);
		echo json_encode(array('alert' => "Das Voting hat um $voting_over_time_print geendet!"));
		exit;
	}
}

function getAllVotes() {
	$votes = array();

	if (file_exists(VOTE_FILE)) {
		$votes = json_decode(file_get_contents(VOTE_FILE), true);
		// corrupt file
		if (!$votes)
			$votes = array();

		// clear old (5 hours) vote data
		if ($votes['access'] < strtotime('-5 hours'))
			$votes = array();
	}

	return $votes;
}

// gets the vote of the user
function getUserVote() {
	$votes = getAllVotes();
	return isset($votes['venue'][get_identifier_ip()]) ? $votes['venue'][get_identifier_ip()] : null;
}

function vote_summary_html($votes, $display_menus = false, $show_js_actions = true) {
	$html = '';

	if (isset($votes['venue']) && is_array($votes['venue']) && !empty($votes['venue'])) {
		// get venue ratings
		$venue_rating = array();
		$anti_verweigerer = 0;

		foreach ($votes['venue'] as $user => $vote_data) {
			foreach ($vote_data as $venue => $vote) {
				if ($venue != 'special') {
					if (!isset($venue_rating[$venue]))
						$venue_rating[$venue] = ($vote == 'up') ? 1 : -1;
					else
						$venue_rating[$venue] += ($vote == 'up') ? 1 : -1;
				}
			}
			if (
				!empty($vote_data) ||
				(isset($vote_data['special']) && cnt($vote_data) > 1)
			)
				if (isset($vote_data['special']) && $vote_data['special'] != 'Verweigerung')
					$anti_verweigerer++;
				else if (!isset($vote_data['special']))
					$anti_verweigerer++;
		}

		// remove <= 0 rankings
		foreach ($venue_rating as $key => $value)
			if ($value <= 0)
				unset($venue_rating[$key]);

		$ratings = array_values($venue_rating);
		rsort($ratings);

		// build new grouped by rating list
		$venue_rating_final = array();
		foreach ($ratings as $rating) {
			$venue_rating_final[$rating] = array_keys($venue_rating, $rating);
		}

		// only preserve top 3 for rating
		//$venue_rating_final = array_slice($venue_rating_final, 0, 3, true);

		// table with details
		uksort($votes['venue'], 'ip_username_sort');
		// note: use inline style here for email
		$html .= '<table style="border-spacing: 5px"><tr>
			<th style="text-align: center"><b>Benutzer</b></th>
			<th style="text-align: center"><b>Vote Ups</b></th>
			<th style="text-align: center"><b>Vote Downs</b></th>
			<th style="text-align: center"><b>Notizen</b></th>
		</tr>';
		foreach ($votes['venue'] as $user => $vote_data) {
			$upVotes = array_keys($vote_data, 'up');
			$downVotes = array_keys($vote_data, 'down');
			$specialVote = isset($vote_data['special']) ? $vote_data['special'] : null;

			sort($upVotes);
			sort($downVotes);

			// style adaptions according to vote
			if ($specialVote == 'Verweigerung')
				$row_style = 'color: #f99';
			else if ($specialVote == 'Egal')
				$row_style = 'color: #999';
			else
				$row_style = '';

			// replace classnames with real venue titles
			foreach ($upVotes as &$venue_class) {
				if (class_exists($venue_class)) {
					$venueTmp = new $venue_class;
					$venue_title = '<a href="' . $venueTmp->url . '" target="_blank" title="Homepage" style="color: inherit ! important">' . htmlspecialchars($venueTmp->title) . '</a>';
				}
				else {
					// query nearplace cache (finds fake venues)
					$website = nearplace_cache_search($venue_class);
					$website = isset($website['website']) ? $website['website'] : null;
					// query nearplace details cache (finds already clicked on real venues)
					if ($website === null) {
						$website = nearplace_details_cache_search($venue_class);
						$website = isset($website['website']) ? $website['website'] : null;
					}
					// google fallback
					if ($website === null)
						$website = 'https://www.google.com/search?q=' . urlencode($venue_class);
					$venue_title = '<a href="' . $website . '" target="_blank" title="Homepage" style="color: inherit ! important">' . htmlspecialchars($venue_class) . '</a>';
				}
				// current user => add delete functionality
				if ($show_js_actions && $user == get_identifier_ip())
					$venue_title .= " <sup title='Löschen'><a href='javascript:void(0)' onclick='vote_delete_part(\"{$venue_class}\")' title='' style='color: red ! important'>x</a></sup>";
				// otherwise => add "me too" functionality
				else if ($show_js_actions)
					$venue_title .= " <sup title='Selbiges voten'><a href='javascript:void(0)' onclick='vote_up(\"{$venue_class}\")' style='color: red ! important'>+1</a></sup>";
				$venue_class = $venue_title;
			}
			unset($venue_class);

			// replace classnames with real venue titles
			foreach ($downVotes as &$venue_class) {
				if (class_exists($venue_class)) {
					$venueTmp = new $venue_class;
					$venue_title = '<a href="' . $venueTmp->url . '" target="_blank" title="Homepage" style="color: inherit ! important">' . htmlspecialchars($venueTmp->title) . '</a>';
				}
				else {
					// query nearplace cache (finds fake venues)
					$website = nearplace_cache_search($venue_class);
					$website = isset($website['website']) ? $website['website'] : null;
					// query nearplace details cache (finds already clicked on real venues)
					if ($website === null) {
						$website = nearplace_details_cache_search($venue_class);
						$website = isset($website['website']) ? $website['website'] : null;
					}
					// google fallback
					if ($website === null)
						$website = 'https://www.google.com/search?q=' . urlencode($venue_class);
					$venue_title = '<a href="' . $website . '" target="_blank" title="Homepage" style="color: inherit ! important">' . htmlspecialchars($venue_class) . '</a>';
				}
				// current user => add delete functionality
				if ($show_js_actions && $user == get_identifier_ip())
					$venue_title .= " <sup title='Löschen'><a href='javascript:void(0)' onclick='vote_delete_part(\"{$venue_class}\")' style='color: red ! important'>x</a></sup>";
				// otherwise => add "me too" functionality
				else if ($show_js_actions)
					$venue_title .= " <sup title='Selbiges voten'><a href='javascript:void(0)' onclick='vote_down(\"{$venue_class}\")' style='color: red ! important'>+1</a></sup>";
				$venue_class = $venue_title;
			}
			unset($venue_class);

			// cleanup other data for output
			$specialVote = htmlspecialchars($specialVote);
			// replace urls with an a tag
			$specialVote = preg_replace('/(https?:\/\/[^\s]+)/', '<a href="$1" target="_blank">$1</a>', $specialVote);
			// current user => add delete functionality
			if ($show_js_actions && $user == get_identifier_ip()) {
				if (!empty($specialVote))
					$specialVote .= ' <sup title="Löschen"><a href="javascript:void(0)" onclick="vote_delete_part(\'special\')" style="color: red ! important">x</a></sup>';
				else
					$specialVote = '<a href="javascript:void(0)" title="Notiz setzen" onclick="setNoteDialog()">setzen</a>';
			}
			// otherwise => add "me too" functionality
			else if ($show_js_actions && !empty($specialVote))
				$specialVote .= " <sup title='Selbiges voten'><a href='javascript:void(0)' onclick='vote_special(\"{$specialVote}\")' style='color: red ! important'>+1</a></sup>";

			// prepare data for output
			$upVotes     = empty($upVotes)     ? '-' : implode(', ', $upVotes);
			$downVotes   = empty($downVotes)   ? '-' : implode(', ', $downVotes);
			$specialVote = empty($specialVote) ? '-' : $specialVote;

			$upVotes_style     = ($upVotes     == '-') ? 'color: #999; text-align: center' : 'color: #008000';
			$downVotes_style   = ($downVotes   == '-') ? 'color: #999; text-align: center' : 'color: #FF0000';
			$specialVote_style = ($specialVote == '-') ? 'color: #999; text-align: center' : 'text-align: center';

			$html .= "<tr style='$row_style'>
				<td>" . htmlspecialchars(ip_anonymize($user)) . "</td>
				<td style='$upVotes_style'>{$upVotes}</td>
				<td style='$downVotes_style'>{$downVotes}</td>
				<td style='$specialVote_style'>{$specialVote}</td>
			</tr>";
		}
		$html .= '</table>';

		// top ratings
		$html .= '<table style="border-spacing: 5px">';
		$html .= '<tr><td><b>Ranking:</b></td></tr>';
		$cnt = 1;
		// get all sane rating count
		$all_sane_rating_cnt = 0;
		foreach ($venue_rating_final as $rating => $venues) {
			$all_sane_rating_cnt += $rating;
		}
		foreach ($venue_rating_final as $rating => $venues) {
			// resolve class names to venue titles
			foreach ($venues as &$venue_class) {
				if (class_exists($venue_class)) {
					$venueTmp = new $venue_class;
					$venue_title = htmlspecialchars($venueTmp->title);
				}
				else
					$venue_title = htmlspecialchars($venue_class);
				$venue_class = $venue_title;
			}
			unset($venue_class);

			// mark venues which got >= 50% sane ratings
			// but only if there are multiple venues
			if (count($venue_rating_final) > 1 && ($rating / $all_sane_rating_cnt) >= 0.5)
				$html .= "<tr><td title='Votes >= 50%' style='font-weight: bold'>$cnt. " . implode(', ', $venues) . " [$rating]</td></tr>";
			else
				$html .= "<tr><td>$cnt. " . implode(', ', $venues) . " [$rating]</td></tr>";
			$cnt++;
		}
		if (empty($venue_rating_final))
			$html .= '<tr><td>Kein Ergebnis</td></tr>';
		$html .= '</table>';

		// print menu of rated venues (for email notifier)
		if ($display_menus) {
			$html_menu = '';
			foreach ((array)$venue_rating_final as $venues) {
				foreach ((array)$venues as $venue_class) {
					if (class_exists($venue_class)) {
						$venueTmp = new $venue_class;
						$html_menu .= "<div style='margin: 5px'><span style='font-weight: bold'>{$venueTmp->title}</span>{$venueTmp->getMenuData()}</div>";
					}
				}
			}
			if (isset($html_menu)) {
				$html .= '<div style="margin: 5px; font-weight: bold">Menüs:</div>';
				$html .= $html_menu;
			}
		}
	}
	else
		$html = "<div style='margin: 5px'>Noch keine Daten vorhanden</div>";

	return html_compress($html);
}

?>