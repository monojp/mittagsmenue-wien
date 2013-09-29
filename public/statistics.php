<?php
	require_once('../includes/guihelper.php');
	require_once('header.php');

	define('REGEX_INPUT', '/^[a-zA-z0-9äöüÄÖÜßêèéû<> -]*$/');
?>
</head>
<body>

<?php

// tracking code
if (isset($tracking_code) && !empty($tracking_code))
	echo $tracking_code;

// get keyword
$keyword = '';
if (isset($_POST['keyword']))
	$keyword = mb_strtolower(trim($_POST['keyword']));
else if (isset($_GET['keyword']))
	$keyword = mb_strtolower(trim($_GET['keyword']));
// no (venue) keyword => redirect
if (empty($keyword)) {
	$server = $_SERVER['SERVER_NAME'];
	header("HTTP/1.0 403 Forbidden");
	header("Location: http://$server");
	exit;
}
// get food keyword (search term)
$foodKeyword = '';
if (isset($_POST['food']))
	$foodKeyword = mb_strtolower(trim($_POST['food']));
else if (isset($_GET['food']))
	$foodKeyword = mb_strtolower(trim($_GET['food']));

$errors = array();
// input checks
if (!preg_match(REGEX_INPUT, $foodKeyword) || !preg_match(REGEX_INPUT, $keyword))
	$errors[] = 'Ungültiges Stichwort! Folgende Zeichen sind erlaubt: Buchstaben, Ziffern, Umlaute, Bindestrich, Leerzeichen und ausgewählte Sonderzeichen (ß, ê, è, é, û, <, >)';

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

echo "<h1>Statistik für <span style='color: red'>$keyword</span>:</h1>";
$date = date_from_offset($dateOffset);
echo "<a href='/?date=$date'>Zurück zur Übersicht</a>";
echo '<br /><br />';

$action = htmlspecialchars($_SERVER['REQUEST_URI']);
echo "Stichwort-Suche: <form action='$action' method='post'>
	<input type='text' name='food' value='$foodKeyword'></input>
</form>";

if (!empty($datasetSize)) {
	echo '<img id="loader_stats" src="imagesCommon/loader.gif" width="160" height="24" alt="ladebalken" style="vertical-align: middle" />';
	// tagcloud with ingredients
	// usage see https://github.com/addywaddy/jquery.tagcloud.js
	if (empty($foodKeyword))
		echo '<h2>Tag Cloud</h2>';
	else
		echo '<h2>Tag Cloud bezogen auf "' . $foodKeyword . '"</h2>';
	echo '<div id="tagCloudFoods" style="display: none">';
	$cnt = 1;
	foreach ($foods as $food => $amount) {
		$url = htmlspecialchars('statistics.php?date=' . $date . '&keyword=' . urlencode($keyword) . '&food=' . urlencode($food));
		$food = htmlspecialchars($food);
		echo "<a href='$url' rel='$amount' class='tagCloudElement'>$food</a>";
		$cnt++;
		if ($cnt > 128) break;
	}
	echo '</div>';
	echo '<br />';

	if (empty($foodKeyword))
		echo '<h2>Häufigkeiten</h2>';
	else
		echo '<h2>Häufigkeiten bezogen auf "' . $foodKeyword . '"</h2>';
	arsort($foods);
	echo '<table id="table_ingredients" border="1" class="stats" style="display: none">';
	echo '<thead><tr style="text-align: left">
		<th>Bestandteil</th>
		<th>Daten</th>
		<th class="center">Anzahl</th>
	</tr></thead><tbody>';
	foreach ($foods as $food => $amount) {
		$food_dates = 'Daten: ' . implode(', ', format_date($dates[$food], 'd.m.Y'));
		$url = htmlspecialchars('statistics.php?date=' . $date . '&keyword=' . urlencode($keyword) . '&food=' . urlencode($food));
		// dirty encode & character
		// can't use htmlspecialchars here, because we need those ">" and "<"
		$food = str_replace("&", "&amp;", $food);
		echo "<tr>
			<td>
				<a href='$url'>$food</a>
			</td>
			<td>
				<a href='javascript:void(0)' title='$food_dates'>Anzeigen</a>
			</td>
			<td class='center'>
				$amount
			</td>
		</tr>";
	}
	echo '</tbody></table>';

	arsort($compositionsAbsolute);
	echo '<table id="table_compositions" border="1" class="stats" style="display: none">';
	echo '<thead><tr style="text-align: left">
		<th>Kombination</th>
		<th>Daten</th>
		<th class="center">Anzahl</th>
	</tr></thead><tbody>';
	foreach ($compositionsAbsolute as $food => $data) {
		$amount = $data['cnt'];
		$dates = 'Daten: ' . implode(', ', format_date($data['dates'], 'd.m.Y'));

		// mark each ingredient by an href linking to search
		$food = create_ingredient_hrefs($food, $keyword);
		// dirty encode & character
		// can't use htmlspecialchars here, because we need those ">" and "<"
		$food = str_replace("&", "&amp;", $food);
		echo "<tr>
			<td>
				$food
			</td>
			<td>
				<a href='javascript:void(0)' title='$dates'>Anzeigen</a>
			</td>
			<td class='center'>
				$amount
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
		// init tagcloud
		head.ready("tagcloud", function () {
			$.fn.tagcloud.defaults = {
				size: {start: 10, end: 17, unit: 'pt'},
				color: {start: '#aaa', end: '#f02'}
			};
			$('#tagCloudFoods a').tagcloud();
		});
	});
	head.ready('table', function() {
		$('#table_ingredients').dataTable({
			'aaSorting': [[ 2, 'desc' ]],
			"oLanguage": {
				"sLengthMenu": "Zeige _MENU_ Einträge pro Seite an",
				"sZeroRecords": "Leider nichts gefunden :(",
				"sInfo": "Zeige _START_ bis _END_ von _TOTAL_ Einträgen an",
				"sInfoEmpty": "Zeige 0 bis 0 von 0 Einträgen",
				"sInfoFiltered": "(gefiltert von insgesamt _MAX_ Einträgen)",
				"sSearch": "Filtere Einträge:",
				"oPaginate": {
					"sPrevious": "Vorherige Seite",
					"sNext": "Nächste Seite"
				}
			}
		});
		$('#table_ingredients').show();
		$('#table_compositions').dataTable({
			'aaSorting': [[ 2, 'desc' ]],
			"oLanguage": {
				"sLengthMenu": "Zeige _MENU_ Einträge pro Seite an",
				"sZeroRecords": "Leider nichts gefunden :(",
				"sInfo": "Zeige _START_ bis _END_ von _TOTAL_ Einträgen an",
				"sInfoEmpty": "Zeige 0 bis 0 von 0 Einträgen",
				"sInfoFiltered": "(gefiltert von insgesamt _MAX_ Einträgen)",
				"sSearch": "Filter:",
				"oPaginate": {
					"sPrevious": "Vorherige Seite",
					"sNext": "Nächste Seite"
				}
			}
		});

		$('#tagCloudFoods').show();
		$('#table_compositions').show();
		$('#loader_stats').hide();
	});
</script>

<?
require_once('footer.php');
?>
