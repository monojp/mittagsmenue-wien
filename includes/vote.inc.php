<?php

define('VOTE_FILE', TMP_PATH . 'votes.json');
define('VOTE_MAILS_FILE', TMP_PATH . 'votemails.json');
define('VOTE_NOTE_MAX_LENGTH', 128);

// get's mail config of the user or from all if null
function email_config_get($user = null) {
	if (!file_exists(VOTE_MAILS_FILE))
		return null;

	$data = file_get_contents(VOTE_MAILS_FILE);
	if (empty($data))
		return null;

	$data = json_decode($data, true);
	if (!$data)
		return null;

	if ($user)
		return isset($data[$user]) ? $data[$user] : '';
	else
		return $data;
}

// set's the email of the given user
function email_config_set($user, $email, $vote_reminder, $voted_mail_only) {
	$all_emails = email_config_get();
	$all_emails = is_array($all_emails) ? $all_emails : array();

	// no mail set => remove entry in config
	if (empty($email))
		unset($all_emails[$user]);
	else {
		$all_emails[$user]['email'] = $email;
		$all_emails[$user]['vote_reminder'] = $vote_reminder;
		$all_emails[$user]['voted_mail_only'] = $voted_mail_only;
	}

	$data = json_encode($all_emails, JSON_FORCE_OBJECT);
	return file_put_contents(VOTE_MAILS_FILE, $data);
}

function saveReturnVotes($votes) {
	global $voting_over_time;

	// save timestamp to delete old votings
	$votes['time'] = time();

	// save votes to file
	if (file_put_contents(VOTE_FILE, json_encode($votes)) === FALSE) {
		echo json_encode(array('alert' => "Das Voting konnte nicht gesetzt werden!"));
	}
	// sort and return votes
	else {
		if (isset($votes['venue']) && !empty($votes['venue'])) {

			if (is_array($votes['venue']))
				ksort($votes['venue']);

			echo json_encode(array(
				'voting_over' => (time() >= $voting_over_time),
				'html'        => vote_summary_html($votes, false),
			));
		}
		else
			echo json_encode('');
	}
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
		if ($votes['time'] < strtotime('-5 hours'))
			$votes = array();
	}

	return $votes;
}

function vote_summary_html($votes, $include_head_body_tags) {
	$html = '';
	if ($include_head_body_tags)
		$html .= '<html><head><title>Voting</title></head><body>';

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
		ksort($votes['venue']);
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

			// cleanup data for output
			array_walk($upVotes, function (&$v, $k) { $v = htmlspecialchars($v); });
			array_walk($downVotes, function (&$v, $k) { $v = htmlspecialchars($v); });
			$specialVote = htmlspecialchars($specialVote);

			// replace urls with an a tag
			$specialVote = preg_replace('/(https?:\/\/[^\s]+)/', '<a href="$1" target="_blank">$1</a>', $specialVote);

			// current user => add delete functionality
			if ($user == get_identifier_ip()) {
				array_walk($upVotes, function (&$v, $k) { $v .= ' <sup title="Löschen"><a href="javascript:void(0)" onclick="vote_delete_part(\'' . $v . '\')" style="color: red ! important">x</a></sup>'; });
				array_walk($downVotes, function (&$v, $k) { $v .= ' <sup title="Löschen"><a href="javascript:void(0)" onclick="vote_delete_part(\'' . $v . '\')" style="color: red ! important">x</a></sup>'; });
				if (!empty($specialVote))
					$specialVote .= ' <sup title="Löschen"><a href="javascript:void(0)" onclick="vote_delete_part(\'special\')" style="color: red ! important">x</a></sup>';
				else
					$specialVote = '<a href="javascript:void(0)" title="Notiz setzen" onclick="setNoteDialog()">setzen</a>';
			}

			// prepare data for output
			$upVotes = empty($upVotes) ? '-' : implode(', ', $upVotes);
			$downVotes = empty($downVotes) ? '-' : implode(', ', $downVotes);
			$specialVote = empty($specialVote) ? '-' : $specialVote;

			$upVotes_style = ($upVotes == '-') ? 'text-align: center' : '';
			$downVotes_style = ($downVotes == '-') ? 'text-align: center' : '';
			$specialVote_style = ($specialVote == '-' || count($specialVote) < 7) ? 'text-align: center' : '';

			$html .= "<tr style='$row_style'>
				<td>" . htmlspecialchars(ip_anonymize($user)) . "</td>
				<td style='$upVotes_style'>" . $upVotes . "</td>
				<td style='$downVotes_style'>" . $downVotes . "</td>
				<td style='$specialVote_style'>" . $specialVote . "</td>
			</tr>";
		}
		$html .= '</table>';

		// top ratings
		$html .= '<table style="border-spacing: 5px">';
		$html .= '<tr><td><b>Ranking:</b></td></tr>';
		$cnt = 1;
		foreach ($venue_rating_final as $rating => $venues) {
			sort($venues);
			$html .= "<tr><td>$cnt. " . implode(', ', $venues) . " [$rating]</td></tr>";
			$cnt++;
		}
		if (empty($venue_rating_final))
			$html .= '<tr><td>Kein Ergebnis</td></tr>';
		$html .= '</table>';
	}

	if ($include_head_body_tags)
		$html .= '</body></html>';

	return html_compress($html);
}

?>
