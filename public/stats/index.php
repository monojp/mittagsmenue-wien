<?php

require_once(__DIR__ . '/../../includes/guihelper.php');
require_once(__DIR__ . '/../../includes/CacheHandler_MySql.php');

define('REGEX_INPUT', '/^[a-zA-z0-9äöüÄÖÜßčćêèéû<>\/ -]*$/');

echo get_header_html();

echo "<div id='page_main' data-role='page'>
	<div data-role='header'>
		<h1>Mittagsmenü-Statistiken</h1>
		<a href='" . build_url('/') . "' data-ajax='false' data-role='button' data-inline='true'
				data-mini='true' data-icon='back' class='ui-btn-left'>Zurück zur Übersicht</a>
	</div>
	<div data-role='main' class='ui-content'>";

// get venue and food keyword
$venue = mb_strtolower(get_var('venue'));
$foodKeyword = htmlspecialchars(mb_strtolower(get_var('food')));

$errors = array();
// input checks
if (!preg_match(REGEX_INPUT, $foodKeyword)/* || !preg_match(REGEX_INPUT, $venue)*/)
	$errors[] = htmlspecialchars('Ungültiges Stichwort! Folgende Zeichen sind erlaubt: Buchstaben, Ziffern, Umlaute, Bindestrich, Leerzeichen, Slash und ausgewählte Sonderzeichen (ß, č, ć, ê, è, é, û, <, >)');

// get data from cache
if (empty($errors)) {
	$start = microtime(true);
	$data = getCacheData(/*$venue*/null, $foodKeyword);
	$stats = is_intern_ip() ? CacheHandler_MySql::getInstance()->get_stats() : [];
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
} else {
	$stats = [];
}

// show minimal (no JS) site notice
if (isset($_GET['minimal'])) {
	echo get_minimal_site_notice_html();
}

$date = date_from_offset($dateOffset);

if (is_intern_ip()) {
	if (!isset($_GET['minimal']))
		echo '<table id="table_stats" class="stats" style="display: none">';
	else
		echo '<table id="table_stats" class="stats">';
	echo '<thead><tr style="text-align: left">
		<th>category</th>
		<th>cnt_up</th>
		<th>cnt_down</th>
		<th>ratio</th>
		<th>diff</th>
	</tr></thead><tbody>';
	foreach ($stats as $stat_entry) {
		echo "<tr>
			<td>{$stat_entry['category']}</td>
			<td class='center'>{$stat_entry['cnt_up']}</td>
			<td class='center'>{$stat_entry['cnt_down']}</td>
			<td class='center'>{$stat_entry['ratio']}</td>
			<td class='center'>{$stat_entry['diff']}</td>
		</tr>";
	}
	echo '</tbody></table>';
	echo '<br />';
 }


$action = htmlspecialchars($_SERVER['REQUEST_URI']);
echo "<form action='{$action}' method='post' data-ajax='false'>
	<label for='food'>Menü-Stichwort:</label> <input type='search' id='food' name='food' value='{$foodKeyword}' autofocus />
	<input type='submit' name='submit' value='Suchen' />
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
	echo '<thead><tr>
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
				<a href='$url' data-ajax='false'>{$food_clean}</a>
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
	echo '<br /><br />';

	arsort($compositionsAbsolute);
	if (!isset($_GET['minimal']))
		echo '<table id="table_compositions" class="stats" style="display: none">';
	else
		echo '<br /><table id="table_compositions" class="stats">';
	echo '<thead><tr>
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
}
else {
	if (strlen($foodKeyword) > 0 && strlen($foodKeyword) < 3)
		$errors[] = 'Suche erst ab 3 Zeichen möglich.';
	else if (!empty($foodKeyword))
		$errors[] = 'Nichts gefunden.';

	if (!empty($errors))
		echo '<br /><b>' . implode('<br />', $errors) . '</b><br />';
}

echo '<br />';
if (!empty($diff)) {
	echo '<small>Abfrage erfolgte in ' . round($diff, 2) . ' Sekunden</small>';
}

echo "	</div>";
echo "	<div data-role='footer'>" . get_footer_html() . "</div>";
echo "</div>";
