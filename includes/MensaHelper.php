<?php

function mensa_menu_get($dataTmp, $title_search, $timestamp, &$price_return=null) {
	$posStart = strnposAfter($dataTmp, 'menu-item-text">', strrpos($dataTmp, $title_search), date('N', $timestamp));
	if ($posStart === false)
		return null;

	$posEnd = mb_stripos($dataTmp, '</div>', $posStart);
	$data = mb_substr($dataTmp, $posStart, $posEnd - $posStart);
	$data = strip_tags($data, '<br>');
	//return error_log($data);

	// remove multiple newlines
	$data = preg_replace("/(\n)+/i", "\n", $data);
	$data = trim($data);
	// split per new line
	$foods = explode("\n", $data);
	//return print_r(error_log($data), true);

	$data = null;
	$cnt = 1;
	foreach ($foods as $food) {
		$food = cleanText($food);
		if (!empty($food)) {
			if ($cnt == 1)
				$data .= $food;
			else if (
				($cnt == 2 && mb_stripos($data, 'suppe') !== false) || // suppe, xx
				$cnt == count($foods) // xx, dessert
			)
				$data .= ", $food";
			else if (strpos($food, '€') !== false)
				$price_return = $food;
			else
				$data .= " $food";
			$cnt++;
		}
	}
	return empty($data) ? '-' : $data;
}
