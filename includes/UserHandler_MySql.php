<?php

require_once(__DIR__ . '/UserHandler.php');
require_once(__DIR__ . '/MysqlConnection.php');

class UserHandler_MySql extends UserHandler {

	private static $instance;
	protected $db = null;

	function __construct() {
		$this->db = MysqlConnection::getInstance()->getConnection();
	}

	public static function getInstance() {
		if (is_null(self::$instance))
			self::$instance = new self();
		return self::$instance;
	}

	private function db_ok() {
		return ($this->db !== null);
	}

	public function save($ip, $name, $email, $vote_reminder, $voted_mail_only, $vote_always_show) {
		if (!$this->db_ok())
			return;

		// prepare statement
		if (!($stmt = $this->db->prepare("INSERT INTO foodUser VALUES (?, ?, ?, ?, ?)")))
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		// bind params
		if (!$stmt->bind_param("sssiii", $ip, $name, $email, $vote_reminder, $voted_mail_only, $vote_always_show))
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		// execute
		if (!$stmt->execute())
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		$stmt->free_result();
	}

	public function update($ip, $name, $email, $vote_reminder, $voted_mail_only, $vote_always_show) {
		if (!$this->db_ok())
			return;

		// prepare statement
		if (!($stmt = $this->db->prepare("UPDATE foodUser SET name=?, email=?, vote_reminder=?, voted_mail_only=?, vote_always_show=? WHERE ip=?")))
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		// bind params
		if (!$stmt->bind_param("ssiiis", $name, $email, $vote_reminder, $voted_mail_only, $vote_always_show, $ip))
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		// execute
		if (!$stmt->execute())
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		$stmt->free_result();
	}

	public function get($ip = null) {
		if (!$this->db_ok())
			return;
		$return = array();

		// prepare statement
		if ($ip && !($stmt = $this->db->prepare("SELECT ip, name, email, vote_reminder, voted_mail_only, vote_always_show FROM foodUser WHERE ip=?")))
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		else if (!$ip && !($stmt = $this->db->prepare("SELECT ip, name, email, vote_reminder, voted_mail_only, vote_always_show FROM foodUser")))
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);

		// bind params
		if ($ip && !$stmt->bind_param("s", $ip))
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		// execute
		if (!$stmt->execute())
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		// bind result variables
		if (!$stmt->bind_result($ip, $name, $email, $vote_reminder, $voted_mail_only, $vote_always_show))
			return error_log("Binding results failed: (" . $stmt->errno . ") " . $stmt->error);
		// fetch results
		while ($stmt->fetch()) {
			$return[$ip] = array(
				'ip' => $ip,
				'name' => $name,
				'email' => $email,
				'vote_reminder' => $vote_reminder,
				'voted_mail_only' => $voted_mail_only,
				'vote_always_show' => $vote_always_show,
			);
		}
		$stmt->free_result();

		return $return;
	}

	public function get_ip($custom_userid = null) {
		if (!$this->db_ok())
			return;
		$return = array();

		// prepare statement
		if (!($stmt = $this->db->prepare("SELECT ip FROM foodCustomUser WHERE custom_userid=?")))
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		// bind params
		if (!$stmt->bind_param("s", $custom_userid))
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		// execute
		if (!$stmt->execute())
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		// bind result variables
		if (!$stmt->bind_result($ip))
			return error_log("Binding results failed: (" . $stmt->errno . ") " . $stmt->error);
		// fetch results
		while ($stmt->fetch()) {
			$return[] = array(
				'ip' => $ip,
			);
		}
		$stmt->free_result();

		return $return;
	}

	public function get_custom_userid($ip = null) {
		if (!$this->db_ok())
			return;
		$return = array();

		// prepare statement
		if (!($stmt = $this->db->prepare("SELECT custom_userid FROM foodCustomUser WHERE ip=?")))
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		// bind params
		if (!$stmt->bind_param("s", $ip))
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		// execute
		if (!$stmt->execute())
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		// bind result variables
		if (!$stmt->bind_result($custom_userid))
			return error_log("Binding results failed: (" . $stmt->errno . ") " . $stmt->error);
		// fetch results
		while ($stmt->fetch()) {
			$return[] = array(
				'custom_userid' => $custom_userid,
			);
		}
		$stmt->free_result();

		return $return;
	}

	public function save_custom_userid($ip, $custom_userid) {
		if (!$this->db_ok())
			return;

		// prepare statement
		if (!($stmt = $this->db->prepare("INSERT INTO foodCustomUser VALUES (?, ?)")))
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		// bind params
		if (!$stmt->bind_param("ss", $ip, $custom_userid))
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		// execute
		if (!$stmt->execute())
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		$stmt->free_result();
	}

	public function update_custom_userid($ip, $custom_userid) {
		if (!$this->db_ok())
			return;

		// prepare statement
		if (!($stmt = $this->db->prepare("UPDATE foodCustomUser SET custom_userid=? WHERE ip=?")))
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		// bind params
		if (!$stmt->bind_param("ss", $custom_userid, $ip))
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		// execute
		if (!$stmt->execute())
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		$stmt->free_result();
	}

}
