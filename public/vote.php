<?php

	require_once('../includes/includes.php');
	require_once('../includes/vote.inc.php');

	header("Vary: Accept-Encoding");
	header("Content-Type: text/html; charset=UTF-8");

	global $voting_over_time;

	if (!is_intern_ip()) {
		echo json_encode(array('alert' => 'Zugriff verweigert!'));
		exit;
	}

	// check identifier if valid vote
	$identifier = isset($_POST['identifier']) ? $_POST['identifier'] : null;
	if ($identifier) {
		$identifier = trim($_POST['identifier']);
		// check if valid vote
		/*if (!in_array($identifier, $vote_valid_identifiers)) {
			echo json_encode(array('alert' => 'Der angegebene Identifier ist unbekannt!'));
			exit;
		}*/
		// TODO new checks
	}
	$ipPrint = ip_anonymize(get_identifier_ip());

	$action = get_var('action');

	// --------------
	// handle actions
	if ($action) {

		$votes = getAllVotes();

		// delete vote
		if ($action == 'vote_delete') {
			check_voting_time();

			unset($votes['venue'][$ipPrint]);

			saveReturnVotes($votes);
		}
		// vote up
		else if ($action == 'vote_up') {
			check_voting_time();

			if (!$identifier) {
				echo json_encode(array('alert' => 'Es wurde kein Identifier angegeben!'));
				exit;
			}

			// delete special vote if set and no user comment
			if (isset($votes['venue'][$ipPrint]['special']) && in_array($votes['venue'][$ipPrint]['special'], array('Egal', 'Verweigerung')))
				unset($votes['venue'][$ipPrint]['special']);

			$votes['venue'][$ipPrint][$identifier] = 'up';
			ksort($votes['venue'][$ipPrint]);

			// limit amount of up votes
			$down_cnt = 0;
			foreach ($votes['venue'][$ipPrint] as $vote) {
				if ($vote == 'up')
					$down_cnt++;
			}
			if ($down_cnt > 3) {
				echo json_encode(array('alert' => 'Bitte nicht mehr als 3 Lokale aufwerten. Es kann auch "Egal" gevoted werden ;)'));
				exit;
			}
			else
				saveReturnVotes($votes);
		}
		// vote down
		else if ($action == 'vote_down') {
			check_voting_time();

			if (!$identifier) {
				echo json_encode(array('alert' => 'Es wurde kein Identifier angegeben!'));
				exit;
			}

			// delete special vote if set and no user comment
			if (isset($votes['venue'][$ipPrint]['special']) && in_array($votes['venue'][$ipPrint]['special'], array('Egal', 'Verweigerung')))
				unset($votes['venue'][$ipPrint]['special']);

			$votes['venue'][$ipPrint][$identifier] = 'down';
			ksort($votes['venue'][$ipPrint]);

			// limit amount of down votes
			$down_cnt = 0;
			foreach ($votes['venue'][$ipPrint] as $vote) {
				if ($vote == 'down')
					$down_cnt++;
			}
			if ($down_cnt > 3) {
				echo json_encode(array('alert' => 'Bitte nicht mehr als 3 Lokale abwerten. Es kann auch "Verweigerung" gevoted werden ;)'));
				exit;
			}
			else
				saveReturnVotes($votes);
		}
		// vote special
		else if ($action == 'vote_special') {
			check_voting_time();

			if (!$identifier) {
				echo json_encode(array('alert' => 'Es wurde kein Identifier angegeben!'));
				exit;
			}

			// delete other votes
			unset($votes['venue'][$ipPrint]);

			$votes['venue'][$ipPrint]['special'] = $identifier;
			ksort($votes['venue'][$ipPrint]);

			saveReturnVotes($votes);
		}
		// set note
		else if ($action == 'vote_set_note') {
			check_voting_time();

			$note = trim($_POST['note']);
			$badwords = cleanText(file_get_contents('../includes/badwords'));
			$badwords = explode("\n", $badwords);

			$note = str_replace_array($badwords, '***', $note);

			// check vote length
			if (strlen($note) > 64) {
				echo json_encode(array('alert' => 'Die Notiz ist zu lange! Es sind max. 64 Zeichen erlaubt!'));
				exit;
			}

			// check vote via regex
			if (!preg_match('/^[A-Za-z0-9äöüß@ ,;\.:_#\*\?\!()-]*$/', $note)) {
				echo json_encode(array('alert' => 'Die Notiz enthält ungültige Sonderzeichen. Erlaubt sind folgende Zeichen: A-Z, a-z, 0-9, "@", ",", ";", ".", ":", "_", "#", "*", "?", "!", "-", "(", ")" und Umlaute'));
				exit;
			}

			$votes['venue'][$ipPrint]['special'] = $note;
			ksort($votes['venue'][$ipPrint]);

			// delete entry if empty note is the only vote
			if (empty($note) && count($votes['venue'][$ipPrint]) == 1)
				unset($votes['venue'][$ipPrint]);

			saveReturnVotes($votes);
		}
		// get all votes
		else if ($action == 'vote_get') {
			if (isset($votes['venue']) && !empty($votes['venue'])) {
				// sort and return votes
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
		// undefined action
		else {
			echo json_encode(array('alert' => 'Die übertragene Aktion ist ungültig!'));
		}
	}
	else {
		echo json_encode(array('alert' => 'Es wurde keine Aktion übertragen!'));
	}

?>
