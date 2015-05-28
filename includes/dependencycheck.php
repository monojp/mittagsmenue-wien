<?php

if (!file_exists(__DIR__ . '/config.php'))
	die('config file not existing');

$commands_to_check = [ 'pdftohtml', 'antiword', 'convert', 'pdftotext' ];
foreach ($commands_to_check as $command) {
	if (!command_exist($command))
		die("command '${command}' not existing");
}

$classes_to_check = [ 'Normalizer' ];
foreach ($classes_to_check as $class) {
	if (!class_exists($class))
		die("class '${class}' not existing");
}
