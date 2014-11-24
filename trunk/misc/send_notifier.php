<?php

require_once(__DIR__ . '/../includes/includes.php');
require_once(__DIR__ . '/../includes/vote.inc.php');

function wrap_in_email_html($body, $custom_userid_access_url) {
	$css_basic = file_get_contents(__DIR__ . '/../public/css/basic.css');
	$html  = '<!DOCTYPE html><html xmlns="http://www.w3.org/1999/xhtml" lang="de" xml:lang="de"><head><title>Voting</title><style type="text/css">' . $css_basic . '</style><meta charset="UTF-8"/></head><body>';
	$html .= $body;
	$html .= '<br />';
	$html .= "<div style='margin: 5px'>Adresse für den externen Zugriff: <a href='{$custom_userid_access_url}' style='font-weight: bold'>{$custom_userid_access_url}</a></div>";
	$html .= '</body></html>';
	return $html;
}

$valid_actions = array(
	'remind',
	'notify',
	'dryrun',
	'remind_html_test',
);

// get action paramter
if (count($argv) == 2)
	$action = trim($argv[1]);
if (!isset($action) || !$action)
	$action = get_var('action');
if (!in_array($action, $valid_actions))
	exit("error, parameter action [" . implode('|', $valid_actions) . "] invalid!\n");

$votes = getAllVotes();

// build mail headers
$headers   = array();
$headers[] = "From: " . SITE_FROM_MAIL;
$headers[] = "MIME-Version: 1.0";
$headers[] = "Content-type: text/html; charset=utf-8";
$headers[] = "X-Mailer: PHP/" . phpversion();
$headers[] = "Precedence: bulk";

$voting_over_time_print = date('H:i', $voting_over_time);

// loop user configs and send emails
$user_configs = user_config_get();
foreach ((array)$user_configs as $ip => $user_config) {
	$user = ip_anonymize($ip);
	// get user config values
	$email           = isset($user_config['email']) ? $user_config['email'] : '';
	$vote_reminder   = isset($user_config['vote_reminder']) ? $user_config['vote_reminder'] : false;
	$vote_reminder   = filter_var($vote_reminder, FILTER_VALIDATE_BOOLEAN);
	$voted_mail_only = isset($user_config['voted_mail_only']) ? $user_config['voted_mail_only'] : false;
	$voted_mail_only = filter_var($voted_mail_only, FILTER_VALIDATE_BOOLEAN);

	// no valid email
	if (empty($email) || $email == 'null')
		continue;

	// user not voted, but wants mails only if votes => continue
	if ($voted_mail_only && !isset($votes['venue'][$ip]))
		continue;

	// get/generate custom_userid_access_url
	$custom_userid = custom_userid_get($ip);
	if (!$custom_userid)
		$custom_userid = custom_userid_generate($ip);
	$custom_userid_access_url = custom_userid_access_url_get($custom_userid);

	// notify, votes exist and valid
	if ($action == 'notify' && $votes && !empty($votes['venue'])) {
		// build html
		$html = vote_summary_html($votes, true, false);
		$html = wrap_in_email_html($html, $custom_userid_access_url);
		$html = html_compress($html);

		$success = mb_send_mail($email, "Voting-Ergebnis", $html, implode("\r\n", $headers));
		if (!$success)
			echo "error sending email to {$email}";
	}
	// remind
	else if ($action == 'remind' && $vote_reminder && !isset($votes['venue'][$ip])) {
		// build html
		$html = "<div style='margin: 5px'>Das Voting endet um <b>{$voting_over_time_print}</b>. Bitte auf <a href='" . SITE_URL . "'><b>" . SITE_URL . "</b></a> voten!</div>";
		$html = wrap_in_email_html($html, $custom_userid_access_url);
		$html = html_compress($html);

		$success = mb_send_mail($email, "Voting-Erinnerung", $html, implode("\r\n", $headers));
		if (!$success)
			echo "error sending email to {$email}";
	}
	// dryrun to check who will get emails
	else if ($action == 'dryrun') {
		if ($vote_reminder && !isset($votes['venue'][$ip]))
			echo "would send a remind email to {$email}\n";
		else if ($votes && !empty($votes['venue']))
			echo "would send a notify email to {$email}\n";
	}
	// remind html output test
	else if ($action == 'remind_html_test') {
		$html = vote_summary_html($votes, true, false);
		$html = wrap_in_email_html($html, $custom_userid_access_url);
		$html = html_compress($html);
		echo $html;
		break;
	}
}

?>