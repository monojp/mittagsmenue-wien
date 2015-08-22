<?php

function mensa_menu_get($dataTmp, $title_search, $timestamp, &$price_return=null) {
	$title_pos = strrpos($dataTmp, $title_search);
	if ($title_pos === false) {
		return null;
	}
	$posStart = strnposAfter($dataTmp, 'menu-item-text">', $title_pos, date('N', $timestamp));
	if ($posStart === false) {
		return null;
	}

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
			if ($cnt == 1) {
				$data .= $food;
			} else if (
				($cnt == 2 && mb_stripos($data, 'suppe') !== false) || // suppe, xx
				$cnt == count($foods) // xx, dessert
			) {
				$data .= ", $food";
			} else if (strpos($food, '€') !== false) {
				if ($price_return !== null) {
					$price_return = $food;
				}
			} else {
				$data .= " $food";
			}
			$cnt++;
		}
	}
	return empty($data) ? '-' : $data;
}
