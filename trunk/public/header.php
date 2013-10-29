<?php
	require_once('../includes/includes.php');

	header("Vary: Accept-Encoding");
	header("Content-Type: text/html; charset=UTF-8");

	// cache 1 hour
	$seconds_to_cache = 3600;
	$ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . " GMT";
	header("Expires: $ts");
	header("Pragma: cache");
	header("Cache-Control: private, post-check=900, pre-check=$seconds_to_cache, max-age=$seconds_to_cache");
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php echo META_KEYWORDS ?></title>
<meta http-equiv="Content-type" content="text/html;charset=UTF-8"></meta>
<meta name="title" content="Mittagsmenüs in Wien"></meta>
<meta name="robots" content="INDEX,FOLLOW"></meta>
<meta name="keywords" content="<?php echo META_KEYWORDS ?>"></meta>
<meta name="description" content="<?php echo META_DESCRIPTION ?>"></meta>
<meta name="viewport" content="width=device-width"></meta>

<?php
	require_once('../includes/venues.php');

	// css
	if (USE_MINIMZED_JS_CSS) {
		$stylesCSS = 'css/styles-min.css';
	}
	else {
		$stylesCSS = 'css/styles.css';
	}
	// default css
	echo '<link rel="stylesheet" type="text/css" href="' . cacheSafeUrl($stylesCSS) . '" />';
	// jquery-ui css when not minimal
	if (!isset($_GET['minimal']))
		echo '<link rel="stylesheet" type="text/css" href="' . cacheSafeUrl('css/jquery-ui-1.10.3.custom.min.css') . '" />';

	// javascript
	$headLoadJS = 'js/head.min.js';
	$jqueryJS = 'js/jquery-1.10.2.min.js';
	$jqueryUiJS = 'js/jquery-ui-1.10.3.custom.min.js';
	if (USE_MINIMZED_JS_CSS) {
		$datePickerLocalJS = 'js/jquery.ui.datepicker-de-min.js';
		$scriptsJS = 'js/scripts-min.js';
		$jqueryCookieJS = 'js/jquery.cookie-min.js';
		$tagCloudJS = 'js/jquery.tagcloud-min.js';
		$tableJS = 'js/jquery.dataTables-min.js';
	}
	else {
		$datePickerLocalJS = 'js/jquery.ui.datepicker-de.js';
		$scriptsJS = 'js/scripts.js';
		$jqueryCookieJS = 'js/jquery.cookie.js';
		$tagCloudJS = 'js/jquery.tagcloud.js';
		$tableJS = 'js/jquery.dataTables.js';
	}
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
						{tagcloud: "' . cacheSafeUrl($tagCloudJS) .'"},
						{table: "' . cacheSafeUrl($tableJS) .'"}
					);
				});
			</script>
		';
?>
