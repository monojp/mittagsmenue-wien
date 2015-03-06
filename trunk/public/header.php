<?php

require_once(__DIR__ . '/../includes/includes.php');

// start output buffering with custom html compress handler
ob_start('html_compress');

echo '<!DOCTYPE html>';
echo '<html lang="de">';
echo '<head>';
echo '<title>' . META_KEYWORDS . '</title>';
echo '<meta charset="UTF-8" />';
echo '<meta name="robots" content="INDEX,FOLLOW" />';
echo '<meta name="keywords" content="' . META_KEYWORDS . '" />';
echo '<meta name="description" content="' .  META_DESCRIPTION . '" />';
echo '<meta name="viewport" content="width=device-width" />';

require_once(__DIR__ . '/../includes/venues.php');

// css and javascript
$stylesCSS = 'css/styles.css';
$headLoadJS = 'js/head.load.min.js';
$scriptsJS = 'js/scripts.js';
if (USE_MINIMZED_JS_CSS_HTML) {
	$stylesCSS = 'css/styles-min.css';
	$scriptsJS = 'js/scripts-min.js';
}
// basic css
echo '
	<link rel="stylesheet" type="text/css" href="' . cacheSafeUrl($stylesCSS) . '" />
';
// javascript
if (!isset($_GET['minimal']))
	echo '
		<script src="' . cacheSafeUrl($headLoadJS) . '" type="text/javascript"></script>
		<script type="text/javascript">
			head.js(
				{scripts: "' . cacheSafeUrl($scriptsJS) . '"}
			);
		</script>
	';
