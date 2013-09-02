<?php

require_once('../includes/config.php');

echo '<br /><br />';

$outputs = array();
if (CONTACT_HREF)
	$outputs[] = '<a href="' . htmlspecialchars(CONTACT_HREF) . '" target="_blank">Kontakt</a>';
if (IMPRESSUM_HREF)
	$outputs[] = '<a href="' . htmlspecialchars(IMPRESSUM_HREF) . '" target="_blank">Impressum</a>';
if (PRIVACY_INFO)
	$outputs[] = '<a href="javascript:void(0)" title="' . htmlspecialchars(PRIVACY_INFO) . '">Datenschutz-Hinweis</a>';

echo implode(' | ', $outputs);

echo '<br /><br /></body></html>';

?>
