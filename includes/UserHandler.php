<?php

require_once(__DIR__ . '/includes.php');

abstract class UserHandler {
	abstract public function save($ip, $name, $email, $vote_reminder, $voted_mail_only, $vote_always_show);
	abstract public function update($ip, $name, $email, $vote_reminder, $voted_mail_only, $vote_always_show);
	abstract public function get($ip);

	abstract public function get_ip($custom_userid);
	abstract public function get_custom_userid($ip);
	abstract public function save_custom_userid($ip, $custom_userid);
	abstract public function update_custom_userid($ip, $custom_userid);
}
