<?php

require_once(__DIR__ . '/includes.php');

abstract class VoteHandler {
	abstract public function save($day, $ip, $category, $vote);
	abstract public function update($day, $ip, $category, $vote);
	abstract public function get($day, $ip, $category);
	abstract public function delete($day, $ip, $category);
	abstract public function get_weekly($weeknumber, $yearnumber);
}
