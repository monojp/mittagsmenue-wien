<?php

require_once('FoodGetterVenue.php');

/*
 * loads all venue classes from the venues dir
 */
$handle = opendir(dirname(__FILE__) . '/venues');
if ($handle) {
	while (false !== ($file = readdir($handle))) {
		if ($file != '..' && $file != '.')
			require_once "venues/$file";
	}
}

/*
 * (maybe) TODO
 * Stöger Mittagsmenü
 * wirr: http://www.wirr.at/v2/php/index.php?page=eat
 * Waldviertlerhof: http://www.waldviertlerhof.at/htm/wochenmenue.doc
 * nam nam: http://www.nam-nam.at/pages/de/wochenmenu.php
 * falkensteiner stüberl: http://www.falkensteinerstueberl.at/menueplankleistgasse.pdf
 * http://menu.mensen.at/index/index/locid/9
 * www.cafedrechsler.at
 * www.9erbraeu.at
 * www.adam.at
 * www.alborgo.at
 * www.bierheuriger-gangl.at
 * cafehummel.at
 * www.lacantinetta.at
 * www.ef16.at
 * www.flosz.at
 * www.hollmann-salon.at
 * www.lutz-bar.at
 * www.martinjak.com
 * www.horvath.at
 * www.zwoelf-apostelkeller.at
 */


?>
