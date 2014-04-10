<?php
	require_once(__DIR__ . '/../includes/includes.php');

	// start output buffering with custom html compress handler
	ob_start('html_compress');

	echo '<!DOCTYPE html>';
	echo '<html xmlns="http://www.w3.org/1999/xhtml">';
	echo '<head>';
	echo '<title>' . META_KEYWORDS . '</title>';
	echo '<meta charset="UTF-8" />';
	echo '<meta name="robots" content="INDEX,FOLLOW" />';
	echo '<meta name="keywords" content="' . META_KEYWORDS . '" />';
	echo '<meta name="description" content="' .  META_DESCRIPTION . '" />';
	echo '<meta name="viewport" content="width=device-width" />';

	require_once(__DIR__ . '/../includes/venues.php');

	// css and javascript
	$headLoadJS = 'js/head.load.min.js';
	$jqueryJS = 'js/jquery-1.11.0.min.js';
	$jqueryUiJS = 'js/jquery-ui-1.10.4.custom.min.js';
	if (USE_MINIMZED_JS_CSS_HTML) {
		$stylesCSS = 'css/styles-min.css';
		$jqeryuiCSS = 'css/black-tie/jquery-ui-1.10.4.custom.min.css';
		$datePickerLocalJS = 'js/jquery.ui.datepicker-de-min.js';
		$scriptsJS = 'js/scripts-min.js';
		$jqueryCookieJS = 'js/jquery.cookie-min.js';
		$tableJS = 'js/jquery.dataTables-min.js';
	}
	else {
		$stylesCSS = 'css/styles.css';
		$jqeryuiCSS = 'css/black-tie/jquery-ui-1.10.4.custom.css';
		$datePickerLocalJS = 'js/jquery.ui.datepicker-de.js';
		$scriptsJS = 'js/scripts.js';
		$jqueryCookieJS = 'js/jquery.cookie.js';
		$tableJS = 'js/jquery.dataTables.js';
	}
	// basic css
	echo '
		<link rel="stylesheet" type="text/css" href="' . cacheSafeUrl($stylesCSS) . '" />
		<link rel="stylesheet" type="text/css" href="' . cacheSafeUrl($jqeryuiCSS) . '" />
	';
	// javascript
	if (!isset($_GET['minimal']))
		echo '
			<script src="' . cacheSafeUrl($headLoadJS) . '" type="text/javascript"></script>
			<script type="text/javascript">
				head.js("' . cacheSafeUrl($jqueryJS) . '", function() {
					head.js(
						{scripts: "' . cacheSafeUrl($scriptsJS) . '"},
						{cookie: "' . cacheSafeUrl($jqueryCookieJS) .'"}
					);
				});
				head.js("' . cacheSafeUrl($jqueryUiJS) . '", function() {
					head.js(
						{jqueryui_datepicker_de: "' . cacheSafeUrl($datePickerLocalJS). '"},
						{table: "' . cacheSafeUrl($tableJS) .'"}
					);
				});
			</script>
		';
?>