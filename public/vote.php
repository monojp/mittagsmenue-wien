<?php

require_once('../includes/includes.php');
require_once('../includes/vote.inc.php');

global $voting_over_time;

if (!is_intern_ip()) {
	exit(json_encode([ 'alert' => js_message_prepare('access denied') ]));
}

// check identifier if valid vote
$identifier = isset($_POST['identifier']) ? trim($_POST['identifier']) : null;
$ip = get_identifier_ip();
$action = get_var('action');

// delete vote
if ($action == 'vote_delete') {
	check_voting_time();

	VoteHandler_MySql::getInstance($timestamp)->delete(date(VOTE_DATE_FORMAT, $timestamp), $ip);
// delete a vote part
} else if ($action == 'vote_delete_part') {
	check_voting_time();

	if (!$identifier) {
		exit(json_encode([ 'alert' => js_message_prepare('invalid identifier') ]));
	}

	VoteHandler_MySql::getInstance($timestamp)->delete(date(VOTE_DATE_FORMAT, $timestamp),
			$ip, $identifier);
// vote up/down
} else if (in_array($action, [ 'vote_up', 'vote_down' ])) {
	check_voting_time();

	if (!$identifier || !in_array($identifier, $votes_valid_normal)) {
		exit(json_encode([ 'alert' => js_message_prepare('invalid identifier') ]));
	}

	$vote = ($action == 'vote_up') ? 'up' : 'down';
	if (empty(VoteHandler_MySql::getInstance($timestamp)->get(date(VOTE_DATE_FORMAT, $timestamp),
			$ip, $identifier))) {
		VoteHandler_MySql::getInstance($timestamp)->save(date(VOTE_DATE_FORMAT, $timestamp),
				$ip, $identifier, $vote);
	} else {
		VoteHandler_MySql::getInstance($timestamp)->update(date(VOTE_DATE_FORMAT, $timestamp),
				$ip, $identifier, $vote);
	}
// vote special
} else if ($action == 'vote_special') {
	check_voting_time();

	if (!$identifier || !in_array($identifier, $votes_valid_special)) {
		exit(json_encode([ 'alert' => js_message_prepare('invalid identifier') ]));
	}

	$votes['venue'][$ip]['special'] = $identifier;
	ksort($votes['venue'][$ip]);

	$vote_data = VoteHandler_MySql::getInstance($timestamp)->get(date(VOTE_DATE_FORMAT, $timestamp),
			$ip, 'special');
	if (empty($vote_data)) {
		VoteHandler_MySql::getInstance($timestamp)->save(date(VOTE_DATE_FORMAT, $timestamp), $ip,
				'special', $identifier);
	} else {
		VoteHandler_MySql::getInstance($timestamp)->update(date(VOTE_DATE_FORMAT, $timestamp), $ip,
				'special', $identifier);
	}
// set note
} else if ($action == 'vote_set_note') {
	check_voting_time();

	$note = trim($_POST['note']);

	// check vote length
	if (mb_strlen($note) > VOTE_NOTE_MAX_LENGTH) {
		exit(json_encode([ 'alert' => js_message_prepare('Die Notiz ist zu lange! Es sind max. '
				. VOTE_NOTE_MAX_LENGTH . ' Zeichen erlaubt!') ]));
	} else if (empty($note)) {
		exit(json_encode([ 'alert' => js_message_prepare('Bitte eine Notiz angeben!') ]));
	}

	$vote_data = VoteHandler_MySql::getInstance($timestamp)->get(date(VOTE_DATE_FORMAT, $timestamp),
			$ip, 'special');
	if (empty($vote_data)) {
		VoteHandler_MySql::getInstance($timestamp)->save(date(VOTE_DATE_FORMAT, $timestamp), $ip,
				'special', $note);
	} else {
		VoteHandler_MySql::getInstance($timestamp)->update(date(VOTE_DATE_FORMAT, $timestamp), $ip,
				'special', $note);
	}
} else if ($action != 'vote_get') {
	exit(json_encode([ 'alert' => js_message_prepare('invalid action') ]));
}

// return all votes
returnVotes(getAllVotes());
