<?php

require_once(__DIR__ . '/BannerHandler.php');
require_once(__DIR__ . '/MysqlConnection.php');

class BannerHandler_MySql extends BannerHandler {

	private static $instance;
	protected $db = null;

	function __construct($timestamp=null) {
		$this->db = MysqlConnection::getInstance()->getConnection();
	}

	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function db_ok() {
		return ($this->db !== null);
	}

	/**
	 * Fetches the banner from the given weekday.
	 *
	 * @param int $weekday Weekday.
	 * @return array
	 */
	public function get(int $weekday) {
		if (!$this->db_ok()) {
			return;
		}
		$return = [];

		// prepare statement
		if (!($stmt = $this->db->prepare("SELECT venue, weekday, url FROM foodBanner WHERE weekday = ? LIMIT 1"))) {
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		}

		// bind params
		if (!$stmt->bind_param("i", $weekday)) {
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		}

		// execute
		if (!$stmt->execute()) {
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		// bind result variables
		if (!$stmt->bind_result($venue, $weekday, $url)) {
			return error_log("Binding results failed: (" . $stmt->errno . ") " . $stmt->error);
		}

		// fetch result
		while ($ret = $stmt->fetch()) {
			if ($ret === false) {
				return error_log("Fetching result failed: (" . $stmt->errno . ") " . $stmt->error);
			}

			$return = [
				'venue' => $venue,
				'weekday' => $weekday,
				'url' => $url,
			];
		}
		$stmt->free_result();

		return $return;
	}

}
