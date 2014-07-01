<?php

	require_once('../includes/includes.php');
	require_once('../includes/vote.inc.php');

	global $voting_over_time;

	if (!is_intern_ip()) {
		echo json_encode(array('alert' => js_message_prepare('Zugriff verweigert!')));
		exit;
	}

	// check identifier if valid vote
	$identifier = isset($_POST['identifier']) ? trim($_POST['identifier']) : null;
	$ip = get_identifier_ip();

	$action = get_var('action');

	// --------------
	// handle actions
	if ($action) {

		$votes = getAllVotes();

		// delete vote
		if ($action == 'vote_delete') {
			check_voting_time();

			unset($votes['venue'][$ip]);

			saveReturnVotes($votes);
		}
		// delete a vote part
		else if ($action == 'vote_delete_part') {
			check_voting_time();

			if (!$identifier) {
				echo json_encode(array('alert' => js_message_prepare('Es wurde kein Identifier angegeben!')));
				exit;
			}

			unset($votes['venue'][$ip][$identifier]);

			// no user votes anymore? remove whole user info
			if (empty($votes['venue'][$ip]))
				unset($votes['venue'][$ip]);

			saveReturnVotes($votes);
		}
		// vote up
		else if ($action == 'vote_up') {
			check_voting_time();

			if (!$identifier) {
				echo json_encode(array('alert' => js_message_prepare('Es wurde kein Identifier angegeben!')));
				exit;
			}

			// delete special vote if set and no user comment
			if (isset($votes['venue'][$ip]['special']) && in_array($votes['venue'][$ip]['special'], array('Egal', 'Verweigerung')))
				unset($votes['venue'][$ip]['special']);

			$votes['venue'][$ip][$identifier] = 'up';
			ksort($votes['venue'][$ip]);

			// limit amount of up votes
			$down_cnt = 0;
			foreach ($votes['venue'][$ip] as $vote) {
				if ($vote == 'up')
					$down_cnt++;
			}
			if ($down_cnt > 3) {
				echo json_encode(array('alert' => js_message_prepare('Bitte nicht mehr als 3 Lokale aufwerten. Es kann auch "Egal" gevoted werden ;)')));
				exit;
			}
			else
				saveReturnVotes($votes);
		}
		// vote down
		else if ($action == 'vote_down') {
			check_voting_time();

			if (!$identifier) {
				echo json_encode(array('alert' => js_message_prepare('Es wurde kein Identifier angegeben!')));
				exit;
			}

			// delete special vote if set and no user comment
			if (isset($votes['venue'][$ip]['special']) && in_array($votes['venue'][$ip]['special'], array('Egal', 'Verweigerung')))
				unset($votes['venue'][$ip]['special']);

			$votes['venue'][$ip][$identifier] = 'down';
			ksort($votes['venue'][$ip]);

			// limit amount of down votes
			$down_cnt = 0;
			foreach ($votes['venue'][$ip] as $vote) {
				if ($vote == 'down')
					$down_cnt++;
			}
			if ($down_cnt > 3) {
				echo json_encode(array('alert' => js_message_prepare('Bitte nicht mehr als 3 Lokale abwerten. Es kann auch "Verweigerung" gevoted werden ;)')));
				exit;
			}
			else
				saveReturnVotes($votes);
		}
		// vote special
		else if ($action == 'vote_special') {
			check_voting_time();

			if (!$identifier) {
				echo json_encode(array('alert' => js_message_prepare('Es wurde kein Identifier angegeben!')));
				exit;
			}

			// delete other votes
			unset($votes['venue'][$ip]);

			$votes['venue'][$ip]['special'] = $identifier;
			ksort($votes['venue'][$ip]);

			saveReturnVotes($votes);
		}
		// set note
		else if ($action == 'vote_set_note') {
			check_voting_time();

			$note = trim($_POST['note']);
			$badwords_raw = cleanText(file_get_contents('../includes/badwords'));
			$badwords = explode("\n", $badwords_raw);
			$badwords_noslash = explode("\n", mb_str_replace("'", '', $badwords_raw));
			$badwords_nospace = explode("\n", mb_str_replace(' ', '', $badwords_raw));

			$note = str_ireplace_array($badwords, '***', $note);
			$note = str_ireplace_array($badwords_noslash, '***', $note);
			$note = str_ireplace_array($badwords_nospace, '***', $note);

			// check vote length
			if (strlen($note) > VOTE_NOTE_MAX_LENGTH) {
				echo json_encode(array('alert' => js_message_prepare('Die Notiz ist zu lange! Es sind max. ' . VOTE_NOTE_MAX_LENGTH . ' Zeichen erlaubt!')));
				exit;
			}

			// check vote via regex
			// UPDATE: not used anymore because everything is well escaped and formatted
			/*if (!preg_match('/^[A-Za-z0-9äöüß@ ,;\/\.:_#\*\?\!()-]*$/', $note)) {
				echo json_encode(array('alert' => js_message_prepare('Die Notiz enthält ungültige Sonderzeichen. Erlaubt sind folgende Zeichen: A-Z, a-z, 0-9, "@", ",", ";", ".", ":", "_", "#", "*", "?", "!", "-", "(", ")", "/" und Umlaute')));
				exit;
			}*/

			$votes['venue'][$ip]['special'] = $note;
			ksort($votes['venue'][$ip]);

			// delete entry if empty note is the only vote
			if (empty($note) && count($votes['venue'][$ip]) == 1)
				unset($votes['venue'][$ip]);

			saveReturnVotes($votes);
		}
		// get all votes
		else if ($action == 'vote_get') {
			returnVotes($votes);
		}
		// undefined action
		else {
			echo json_encode(array('alert' => js_message_prepare('Die übertragene Aktion ist ungültig!')));
		}
	}
	else {
		echo json_encode(array('alert' => js_message_prepare('Es wurde keine Aktion übertragen!')));
	}

?>