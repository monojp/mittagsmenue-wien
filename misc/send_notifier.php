<?php

require_once(__DIR__ . '/../includes/includes.php');

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
	$action = $_REQUEST['action'] ?? null;
}
if (!in_array($action, $valid_actions)) {
	exit("error, parameter action [" . implode('|', $valid_actions) . "] invalid!\n");
}

$votes = \App\Legacy\ContainerHelper::getInstance()->get(\App\Service\VoteService::class)->getAllVoteData($timestamp);

// build mail headers
$headers = [];
$headers[] = 'From: ' . mb_encode_mimeheader(getenv('SITE_TITLE')) . '<' . getenv('SITE_FROM_MAIL') . '>';
$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-type: text/html; charset=utf-8";
$headers[] = "X-Mailer: PHP";
$headers[] = "Precedence: bulk";

// loop user configs and send emails
foreach (\App\Legacy\ContainerHelper::getInstance()->get(\App\Service\UserService::class)->getUsers() as $foodUser) {
    $ip = $foodUser->getIp();
	// get user config values
	$email = $foodUser->getEmail();
	$vote_reminder = $foodUser->getVoteReminder();
	$voted_mail_only = $foodUser->getVotedMailOnly();

	// user not voted, but wants mails only if votes => continue
	if ($voted_mail_only && !isset($votes[$ip])) {
		continue;
	}

	// notify, votes exist and valid
	if ($action == 'notify' && $votes && !empty($votes)) {
		// build html
		$html = \App\Legacy\ContainerHelper::getInstance()->get(\App\Service\VoteService::class)->summaryHtml($ip, $votes, true, false);
		$html = wrap_in_email_html($html);

		$success = mb_send_mail($email, "Voting-Ergebnis", $html, implode("\r\n", $headers));
		if (!$success) {
		    \App\Legacy\ContainerHelper::getInstance()->get(\Psr\Log\LoggerInterface::class)->error("could not send notify email to {$email}");
		} else {
            \App\Legacy\ContainerHelper::getInstance()->get(\Psr\Log\LoggerInterface::class)->debug("sent notify email to {$email}");
		}
	// remind
	} elseif ($action == 'remind' && $vote_reminder && !isset($votes[$ip])) {
		// build html
        $voting_over_time = getenv('VOTING_OVER_TIME');
		$html = "<div style='margin: 5px'>Das Voting endet um <b>{$voting_over_time}</b>. Bitte auf <a href='" . getenv('SITE_URL') . "'><b>" . SITE_URL . "</b></a> voten!</div>";
		$html .= '<h3>Zwischenstand:</h3>';
		$html .= \App\Legacy\ContainerHelper::getInstance()->get(\App\Service\VoteService::class)->summaryHtml($ip, $votes, true, false);
		$html = wrap_in_email_html($html);

		$success = mb_send_mail($email, "Voting-Erinnerung", $html, implode("\r\n", $headers));
        if (!$success) {
            \App\Legacy\ContainerHelper::getInstance()->get(\Psr\Log\LoggerInterface::class)->error("could not send remind email to {$email}");
        } else {
            \App\Legacy\ContainerHelper::getInstance()->get(\Psr\Log\LoggerInterface::class)->debug("sent remind email to {$email}");
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
		$html = \App\Legacy\ContainerHelper::getInstance()->get(\App\Service\VoteService::class)->summaryHtml($ip, $votes, true, false);
		$html = wrap_in_email_html($html);
		echo $html;
		break;
	}
}
