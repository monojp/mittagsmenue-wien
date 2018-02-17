<?php

require_once(__DIR__ . '/includes.php');

abstract class BannerHandler {
	abstract public function get(int $weekday);
}
