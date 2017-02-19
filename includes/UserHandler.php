<?php

require_once(__DIR__ . '/includes.php');

abstract class UserHandler {
	abstract public function save($ip, $name, $email, $vote_reminder, $voted_mail_only, $vote_always_show);
	abstract public function update($ip, $name, $email, $vote_reminder, $voted_mail_only, $vote_always_show);
	abstract public function get($ip);
}
