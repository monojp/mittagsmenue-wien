<?php

if (!file_exists(__DIR__ . '/config.php'))
	exit_error('no config found');

$commands_to_check = [ 'pdftohtml', 'antiword', 'convert', 'pdftotext' ];
foreach ($commands_to_check as $command) {
	if (!command_exist($command))
		exit_error("command '${command}' not existing");
}

$classes_to_check = [ 'Normalizer' ];
foreach ($classes_to_check as $class) {
	if (!class_exists($class))
		exit_error("class '${class}' not existing");
}
