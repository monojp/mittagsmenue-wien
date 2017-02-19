<?php

require_once(__DIR__ . '/../includes/includes.php');
require_once(__DIR__ . '/../includes/vote.inc.php');

mb_language('uni');

function wrap_in_email_html($body) {
	return '<!DOCTYPE html><html lang="de"><head><title>Voting</title><style type="text/css">'
        . file_get_contents(__DIR__ . '/../public/css/basic.css')
        . '</style><meta charset="UTF-8"/></head><body>' . $body . '</body></html>';
}

$valid_actions = [ 'remind', 'notify', 'dryrun', 'remind_html_test' ];

// get action paramter
if (count($argv) == 2) {
	$action = trim($argv[1]);
}
if (!isset($action) || !$action) {
	$action = get_var('action');
}
if (!in_array($action, $valid_actions)) {
	exit("error, parameter action [" . implode('|', $valid_actions) . "] invalid!\n");
}

$votes = getAllVotes();

// build mail headers
$headers = [];
$headers[] = 'From: ' . mb_encode_mimeheader(META_KEYWORDS) . '<' . SITE_FROM_MAIL . '>';
$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-type: text/html; charset=utf-8";
$headers[] = "X-Mailer: PHP";
$headers[] = "Precedence: bulk";

$voting_over_time_print = date('H:i', $voting_over_time);

// loop user configs and send emails
foreach ((array)UserHandler_MySql::getInstance()->get() as $ip => $user_config) {
	// get user config values
	$email = isset($user_config['email']) ? $user_config['email'] : '';
	$vote_reminder = isset($user_config['vote_reminder']) ? $user_config['vote_reminder'] : false;
	$vote_reminder = filter_var($vote_reminder, FILTER_VALIDATE_BOOLEAN);
	$voted_mail_only = isset($user_config['voted_mail_only']) ? $user_config['voted_mail_only'] : false;
	$voted_mail_only = filter_var($voted_mail_only, FILTER_VALIDATE_BOOLEAN);

	// no valid email
	if (empty($email) || $email == 'null') {
		continue;
	}

	// user not voted, but wants mails only if votes => continue
	if ($voted_mail_only && !isset($votes[$ip])) {
		continue;
	}

	// notify, votes exist and valid
	if ($action == 'notify' && $votes && !empty($votes)) {
		// build html
		$html = vote_summary_html($votes, true, false, true);
		$html = wrap_in_email_html($html);
		$html = html_compress($html);

		$success = mb_send_mail($email, "Voting-Ergebnis", $html, implode("\r\n", $headers));
		if (!$success) {
			log_debug("error sending email to {$email}");
		} else {
			log_debug("sent notify email to ${email}");
		}
	// remind
	} elseif ($action == 'remind' && $vote_reminder && !isset($votes[$ip])) {
		// build html
		$html = "<div style='margin: 5px'>Das Voting endet um <b>{$voting_over_time_print}</b>. Bitte auf <a href='" . SITE_URL . "'><b>" . SITE_URL . "</b></a> voten!</div>";
		$html .= '<h3>Zwischenstand:</h3>';
		$html .= vote_summary_html($votes, true, false, true);
		$html = wrap_in_email_html($html);
		$html = html_compress($html);

		$success = mb_send_mail($email, "Voting-Erinnerung", $html, implode("\r\n", $headers));
		if (!$success) {
			log_debug("error sending email to {$email}");
		} else {
			log_debug("sent remind email to ${email}");
		}
	// dryrun to check who will get emails
	} elseif ($action == 'dryrun') {
		if ($vote_reminder && !isset($votes[$ip])) {
			echo "would send a remind email to {$email}\n";
		} elseif ($votes && !empty($votes)) {
			echo "would send a notify email to {$email}\n";
		}
	// remind html output test
	} elseif ($action == 'remind_html_test') {
		$html = vote_summary_html($votes, true, false, true);
		$html = wrap_in_email_html($html);
		$html = html_compress($html);
		echo $html;
		break;
	}
}
