<?php

require_once(__DIR__ . '/../../includes/guihelper.php');

define('REGEX_INPUT', '/^[a-zA-z0-9äöüÄÖÜßčćêèéû<>\/ -]*$/');

echo get_header_html();

// get venue and food keyword
$venue = mb_strtolower(get_var('venue'));
$foodKeyword = htmlspecialchars(mb_strtolower(get_var('food')));

$errors = array();
// input checks
if (!preg_match(REGEX_INPUT, $foodKeyword) || !preg_match(REGEX_INPUT, $venue))
	$errors[] = htmlspecialchars('Ungültiges Stichwort! Folgende Zeichen sind erlaubt: Buchstaben, Ziffern, Umlaute, Bindestrich, Leerzeichen, Slash und ausgewählte Sonderzeichen (ß, č, ć, ê, è, é, û, <, >)');

// get data from cache
if (empty($errors)) {
	$start = microtime(true);
	$data = getCacheData($venue, $foodKeyword);
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

echo "<h1>Mittagsmenü-Statistik</h1>";

// show minimal (no JS) site notice
if (isset($_GET['minimal'])) {
	echo get_minimal_site_notice_html();
	echo '<br /><br />';
}

$date = date_from_offset($dateOffset);
echo "<a href='/?date={$date}' data-ajax='false'>Zurück zur Übersicht</a>";
echo '<br /><br />';

$action = htmlspecialchars($_SERVER['REQUEST_URI']);
echo "<form action='{$action}' method='post' data-ajax='false'>
	<label for='venue'>Lokal-Stichwort:</label> <input type='search' id='venue' name='venue' value='{$venue}' /> <br />
	<label for='food'>Stichwort:</label> <input type='search' id='food' name='food' value='{$foodKeyword}' />
	<input type='submit' name='submit' value='submit' />
</form>";

if (!empty($datasetSize)) {
	// loader for javascript
	if (!isset($_GET['minimal']))
		echo '<div id="loader_stats" class="throbber middle">Lade...</div>';

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
		$url = htmlspecialchars('?date=' . $date . '&venue=' . urlencode($venue) . '&food=' . urlencode($food));
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
		//$food = create_ingredient_hrefs($food, $venue, '', true);
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
	head.ready([ 'jquery', 'jquery_datatables' ], function() {
		$(document).ready(function() {
			$('#table_ingredients').dataTable({"order": [[ 2, "desc" ]]});
			$('#table_ingredients').show();
			$('#table_compositions').dataTable({"order": [[ 2, "desc" ]]});
			$('#table_compositions').show();

			$('#loader_stats').hide();
		});
	});
</script>

<?php

echo '<br><br>';
echo get_footer_html();
