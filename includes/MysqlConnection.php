<?php

require_once(__DIR__ . '/VoteHandler.php');

class MySqlConnection {

	private static $instance;
	private $db = null;

	function __construct($timestamp=null) {

		// open db connection, try different configs after each other if connection fails
		global $DB_CONFIGS;
		$this->db = mysqli_init();
		if (!$this->db->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3))
			return error_log('Options Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		$db_connect_ok = false;
		foreach ($DB_CONFIGS as $db_config) {
			if (!$this->db->real_connect($db_config['DB_SERVER'], $db_config['DB_USER'], $db_config['DB_PASSWORD'], $db_config['DB_NAME'])) {
				error_log('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
			} else {
				$db_connect_ok = true;
				break;
			}
		}
		if (!$db_connect_ok) {
			$this->db = null;
			return;
		}
		// set charset
		if (!$this->db->set_charset('utf8mb4')) {
			if (!$this->db->set_charset('utf8')) {
				error_log('could not set charset');
			}
		}
	}

	function __destruct() {
		// abort on error
		if (!$this->db) {
			return null;
		}
		$this->db->close();
	}

	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function getConnection() {
		return $this->db;
	}
}
