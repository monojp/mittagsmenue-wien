<?php

require_once('../includes/config.php');

echo '<br />';

$outputs = array();
if (CONTACT_HREF)
	$outputs[] = '<a href="' . htmlspecialchars(CONTACT_HREF) . '" target="_blank">Kontakt / Fehler melden / Lokalwunsch</a>';
if (IMPRESSUM_HREF)
	$outputs[] = '<a href="' . htmlspecialchars(IMPRESSUM_HREF) . '" target="_blank">Impressum</a>';
if (PRIVACY_INFO)
	$outputs[] = '<a href="javascript:void(0)" title="' . htmlspecialchars(PRIVACY_INFO) . '">Datenschutz-Hinweis</a>';
if (!isset($_GET['minimal'])) {
	$url = '?minimal&date=' . date_from_offset($dateOffset);
	$outputs[] = "<a href='$url' title='Zeigt eine Version dieser Seite ohne JavaScript an'>Minimal-Version</a>";
}

echo implode(' | ', $outputs);

$key_desc = array();
if (META_KEYWORDS)
	$key_desc = array_merge($key_desc, explode(',', META_KEYWORDS));
if (META_DESCRIPTION)
	$key_desc = array_merge($key_desc, explode(',', META_DESCRIPTION));
$key_desc = array_unique($key_desc);
echo '<br /><br />Keywords: ' . implode(', ', $key_desc);

echo '<br /><br /></body></html>';

?>
