<?php

require_once('../includes/guihelper.php');
require_once('header.php');

define('REGEX_INPUT', '/^[a-zA-z0-9äöüÄÖÜßčćêèéû<>\/ -]*$/');

?>
</head>
<body>

<?php

// get keyword
$keyword = '';
if (isset($_POST['keyword']))
	$keyword = mb_strtolower(trim($_POST['keyword']));
else if (isset($_GET['keyword']))
	$keyword = mb_strtolower(trim($_GET['keyword']));
// no (venue) keyword => redirect
if (empty($keyword)) {
	header("HTTP/1.0 403 Forbidden");
	header("Location: http://{$_SERVER['SERVER_NAME']}");
	exit;
}
// get food keyword (search term)
$foodKeyword = '';
if (isset($_POST['food']))
	$foodKeyword = mb_strtolower(trim($_POST['food']));
else if (isset($_GET['food']))
	$foodKeyword = mb_strtolower(trim($_GET['food']));
$foodKeyword = htmlspecialchars($foodKeyword);

$errors = array();
// input checks
if (!preg_match(REGEX_INPUT, $foodKeyword) || !preg_match(REGEX_INPUT, $keyword))
	$errors[] = htmlspecialchars('Ungültiges Stichwort! Folgende Zeichen sind erlaubt: Buchstaben, Ziffern, Umlaute, Bindestrich, Leerzeichen, Slash und ausgewählte Sonderzeichen (ß, č, ć, ê, è, é, û, <, >)');

// get data from cache
if (empty($errors)) {
	$start = microtime(true);
	$data = getCacheData($keyword, $foodKeyword);
	$stop = microtime(true);
	$diff = $stop - $start;

//echo '<pre>' . var_export($data, true) . '</pre>';
//return;

	$datasetSize = isset($data['datasetSize']) ? $data['datasetSize'] : null;
	$foods = isset($data['foods']) ? $data['foods'] : null;
	$dates = isset($data['dates']) ? $data['dates'] : null;
	$compositions = isset($data['compositions']) ? $data['compositions'] : null;
	$compositionsAbsolute = isset($data['compositionsAbsolute']) ? $data['compositionsAbsolute'] : null;

	if ($foods)
		shuffle_assoc($foods);
	if ($compositionsAbsolute)
		shuffle_assoc($compositionsAbsolute);
}

echo "<h1>Statistik für <span style='color: red'>{$keyword}</span>:</h1>";

// show minimal (no JS) site notice
if (isset($_GET['minimal'])) {
	echo get_minimal_site_notice_html();
	echo '<br /><br />';
}
else {
	// piwik user id script
	echo get_piwik_user_id_script();
}

$date = date_from_offset($dateOffset);
echo "<a href='/?date={$date}'>Zurück zur Übersicht</a>";
echo '<br /><br />';

$action = htmlspecialchars($_SERVER['REQUEST_URI']);
echo "Stichwort-Suche: <form action='{$action}' method='post'>
	<input type='search' name='food' value='{$foodKeyword}' />
</form>";

if (!empty($datasetSize)) {
	// loader for javascript
	if (!isset($_GET['minimal']))
		echo '<img id="loader_stats" src="imagesCommon/loader.gif" width="160" height="24" alt="" style="vertical-align: middle" />';

	if (empty($foodKeyword))
		echo '<h2>Häufigkeiten</h2>';
	else
		echo "<h2>Häufigkeiten bezogen auf \"{$foodKeyword}\"</h2>";
	arsort($foods);
	if (!isset($_GET['minimal']))
		echo '<table id="table_ingredients" class="stats" style="display: none">';
	else
		echo '<table id="table_ingredients" class="stats">';
	echo '<thead><tr style="text-align: left">
		<th>Bestandteil</th>
		<th data-dynatable-no-sort="data-dynatable-no-sort">Daten</th>
		<th class="center">Anzahl</th>
	</tr></thead><tbody>';
	foreach ($foods as $food => $amount) {
		$food_dates = 'Daten: ' . implode(', ', format_date($dates[$food], 'd.m.Y'));
		$url = htmlspecialchars('statistics.php?date=' . $date . '&keyword=' . urlencode($keyword) . '&food=' . urlencode($food));
		$food_clean = htmlspecialchars($food);
		echo "<tr>
			<td>
				<a href='$url'>{$food_clean}</a>
			</td>
			<td>
				<a href='javascript:void(0)' title='{$food_dates}' onclick='alert($(this).attr(\"title\"))'>Anzeigen</a>
			</td>
			<td class='center'>
				{$amount}
			</td>
		</tr>";
	}
	echo '</tbody></table>';

	arsort($compositionsAbsolute);
	if (!isset($_GET['minimal']))
		echo '<table id="table_compositions" class="stats" style="display: none">';
	else
		echo '<br /><table id="table_compositions" border="1" class="stats">';
	echo '<thead><tr style="text-align: left">
		<th>Kombination</th>
		<th data-dynatable-no-sort="data-dynatable-no-sort">Daten</th>
		<th class="center">Anzahl</th>
	</tr></thead><tbody>';
	foreach ($compositionsAbsolute as $food => $data) {
		$amount = $data['cnt'];
		$dates = 'Daten: ' . implode(', ', format_date($data['dates'], 'd.m.Y'));

		// mark each ingredient by an href linking to search
		$food = create_ingredient_hrefs($food, $keyword, '', true);
		echo "<tr>
			<td>
				{$food}
			</td>
			<td>
				<a href='javascript:void(0)' title='{$dates}' onclick='alert($(this).attr(\"title\"))'>Anzeigen</a>
			</td>
			<td class='center'>
				{$amount}
			</td>
		</tr>";
	}
	echo '</tbody></table>';
	echo '<div style="clear: both"></div>';
	echo '<small>Cache-Abfrage erfolgte in ' . round($diff, 3) . ' Sekunden</small>';
}
else {
	if (strlen($foodKeyword) > 0 && strlen($foodKeyword) < 3)
		$errors[] = 'Suche erst ab 3 Zeichen möglich.';
	else if (!empty($foodKeyword))
		$errors[] = 'Nichts gefunden.';

	if (!empty($errors))
		echo '<br /><b>' . implode('<br />', $errors) . '</b><br />';
}

?>
<script type="text/javascript">
	head.ready('scripts', function() {
		$('#table_ingredients').dataTable({"order": [[ 2, "desc" ]]});
		$('#table_ingredients').show();
		$('#table_compositions').dataTable({"order": [[ 2, "desc" ]]});
		$('#table_compositions').show();

		$('#loader_stats').hide();
	});
</script>

<?php

require_once('footer.php');
