<?php

	require_once(dirname(__FILE__) . '/../includes/includes.php');
	require_once(dirname(__FILE__) . '/../includes/vote.inc.php');
	require_once(dirname(__FILE__) . '/../includes/weather.inc.php');

	$votes = getAllVotes();

	// get action paramter
	if (count($argv) == 2)
		$action = trim($argv[1]);
	if (!isset($action) || !$action)
		$action = get_var('action');
	if (!in_array($action, array('remind', 'notify')))
		exit("error, parameter action [remind|notify] invalid!\n");

	// build mail headers
	$headers = array();
	$headers[] = "From: " . SITE_FROM_MAIL;
	$headers[] = "MIME-Version: 1.0";
	$headers[] = "Content-type: text/html; charset=utf-8";
	$headers[] = "X-Mailer: PHP/" . phpversion();
	$headers[] = "Precedence: bulk";

	$voting_over_time_print = date('H:i', $voting_over_time);

	// loop user configs and send emails
	$email_configs = email_config_get();
	foreach ((array)$email_configs as $ip => $email_config) {
		$user = ip_anonymize($ip);
		// get user config values
		$email = isset($email_config['email']) ? $email_config['email'] : '';
		$vote_reminder = isset($email_config['vote_reminder']) ? $email_config['vote_reminder'] : false;
		$vote_reminder = filter_var($vote_reminder, FILTER_VALIDATE_BOOLEAN) ? 'checked="checked"' : '';
		$voted_mail_only = isset($email_config['voted_mail_only']) ? $email_config['voted_mail_only'] : false;
		$voted_mail_only = filter_var($voted_mail_only, FILTER_VALIDATE_BOOLEAN) ? 'checked="checked"' : '';

		// user not voted, but wants mails only if votes => continue
		if ($voted_mail_only && !isset($votes['venue'][$user]))
			continue;

		// get/generate custom_userid_access_url
		$custom_userid = custom_userid_get($ip);
		if (!$custom_userid)
			$custom_userid = custom_userid_generate($ip);
		$custom_userid_access_url = custom_userid_access_url_get($custom_userid);

		// notify, votes exist and valid
		if ($action == 'notify' && $votes && !empty($votes['venue'])) {
			// build html
			$html = vote_summary_html($votes, true);
			$html .= '<div style="margin: 10px">' .
				getTemperatureString(false, false) .
			'</div>';
			$html .= "<div style='margin: 0px 10px'>Adresse für den externen Zugriff: <a href='$custom_userid_access_url'>$custom_userid_access_url</a></div>";
			$html = html_compress($html);

			$success = mail($email, "Voting-Ergebnis", $html, implode("\r\n", $headers));
			if ($success)
				echo "sent email to $email";
			else
				echo "error sending email to $email";
		}
		// remind
		else if ($action == 'remind' && $vote_reminder && !isset($votes['venue'][$user])) {
			// build html
			$html = "<div style='margin: 10px'>Das Voting endet um <b>$voting_over_time_print</b>. Bitte auf <a href='" . SITE_URL . "'><b>" . SITE_URL . "</b></a> voten!</div>";
			$html .= "<div style='margin: 0px 10px'>Adresse für den externen Zugriff: <a href='$custom_userid_access_url'>$custom_userid_access_url</a></div>";
			$html = html_compress($html);

			$success = mail($email, "Voting-Erinnerung", $html, implode("\r\n", $headers));
			if ($success)
				echo "sent email to $email";
			else
				echo "error sending email to $email";
		}
	}

?>
