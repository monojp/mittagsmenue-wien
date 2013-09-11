<?php

	require_once(dirname(__FILE__) . '/../includes/includes.php');
	require_once(dirname(__FILE__) . '/../includes/vote.inc.php');
	require_once(dirname(__FILE__) . '/../includes/weather.inc.php');

	$votes = getAllVotes();

	// votes exist and valid
	if ($votes && is_array($votes['venue']) && !empty($votes['venue'])) {

		// build html and header info for mails
		$html = vote_summary_html($votes, true);
		$html .= '<div style="margin: 10px">' .
			getTemperatureString(false, false) .
		'</div>';
		$html .= '<div style="margin: 10px">
			<a href="' . SITE_URL . '">' . SITE_URL . '</a>
		</div>';

		$headers = array();
		$headers[] = "From: " . SITE_FROM_MAIL;
		$headers[] = "MIME-Version: 1.0";
		$headers[] = "Content-type: text/html; charset=utf-8";
		$headers[] = "Subject: Voting-Ergebnis";
		$headers[] = "X-Mailer: PHP/" . phpversion();
		$headers[] = "Precedence: bulk";

		// loop user emails and send
		$emails = array_values(emails_get());
		foreach ($emails as $email) {
			$success = mail($email, "Voting-Ergebnis", $html, implode("\r\n", $headers));

			if ($success)
				echo "sent email to $email";
			else
				echo "error sending email to $email";
		}
	}
	else
		echo "votes not valid or recent";

?>
