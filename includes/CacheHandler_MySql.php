<?php

require_once(__DIR__ . '/CacheHandler.php');
require_once(__DIR__ . '/MysqlConnection.php');

class CacheHandler_MySql extends CacheHandler {

	private static $instance;
	protected $db = null;

	function __construct($timestamp=null) {
		$this->timestamp = $timestamp;
		$this->db = MysqlConnection::getInstance()->getConnection();
	}

	public static function getInstance($timestamp=null) {
		if (is_null(self::$instance)) {
			self::$instance = new self($timestamp);
		}
		return self::$instance;
	}

	private function db_ok() {
		return ($this->db !== null);
	}

	public function saveToCache($venue, $data, $price) {
		if (!$this->db_ok()) {
			return;
		}

		$data = cleanText($data);
		if (empty($data)) {
			return;
		}

		$date = date('Y-m-d', $this->timestamp);
		$price = json_encode($price);

		// prepare statement
		if (!($stmt = $this->db->prepare("INSERT INTO foodCache VALUES (?, ?, NOW(), ?, ?)"))) {
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		}
		// bind params
		if (!$stmt->bind_param("ssss", $venue, $date, $data, $price)) {
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		// execute
		if (!$stmt->execute()) {
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		$stmt->free_result();
	}

	public function getFromCache($venue, &$changed, &$data, &$price) {
		if (!$this->db_ok()) {
			return;
		}

		$date = date('Y-m-d', $this->timestamp);

		// prepare statement
		if (!($stmt = $this->db->prepare("SELECT venue, changed, data, price FROM foodCache WHERE venue=? AND date=?"))) {
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		}
		// bind params
		if (!$stmt->bind_param("ss", $venue, $date)) {
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		// execute
		if (!$stmt->execute()) {
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		// bind result variables
		if (!$stmt->bind_result($venue, $changed, $data, $price)) {
			return error_log("Binding results failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		// fetch results
		$result = $stmt->fetch();
		if ($result === false) {
			return error_log("Fetching results failed: (" . $stmt->errno . ") " . $stmt->error);
		} else if (!$result) {
			return false;
		}
		$stmt->free_result();

		// update 2013-07-23: use json instead of serialized data
		// because of better read- & editability
		// try json_decode first and if failed try unserialize
		// to be backwards compatible
		$price = json_decode($price, true);
		if ($price === NULL && !empty($price)) {
			$price = unserialize($price);
		}
		$data_orig = $data;
		$data = cleanText($data);

		// update modidfied (cleaned) data
		if ($data != $data_orig) {
			$this->updateCache($venue, $data, $price);
		};

		return true;
	}

	public function updateCache($venue, $data, $price) {
		if (!$this->db_ok()) {
			return;
		}

		$data = cleanText($data);
		if (empty($data)) {
			return;
		}

		$date = date('Y-m-d', $this->timestamp);
		$price = json_encode($price);

		// prepare statement
		if (!($stmt = $this->db->prepare("UPDATE foodCache SET changed=NOW(), data=?, price=? WHERE venue=? AND date=?"))) {
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		}
		// bind params
		if (!$stmt->bind_param("ssss", $data, $price, $venue, $date)) {
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		// execute
		if (!$stmt->execute()) {
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		$stmt->free_result();
	}

	public function deleteCache($venue) {
		if (!$this->db_ok()) {
			return;
		}

		$date = date('Y-m-d', $this->timestamp);

		// prepare statement
		if (!($stmt = $this->db->prepare("DELETE FROM foodCache WHERE venue=? AND date=?"))) {
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		}
		// bind params
		if (!$stmt->bind_param("ss", $venue, $date)) {
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		// execute
		if (!$stmt->execute()) {
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		$stmt->free_result();
	}

	public function queryCache($venueKeyword, $dataKeyword) {
		if (!$this->db_ok()) {
			return;
		}

		$return = [];
		$venueKeyword = "%${venueKeyword}%";
		$dataKeyword = "%${dataKeyword}%";

		// prepare statement
		if (!($stmt = $this->db->prepare("SELECT venue, date, data FROM foodCache WHERE venue LIKE ? AND data LIKE ?"))) {
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		}
		// bind params
		if (!$stmt->bind_param("ss", $venueKeyword, $dataKeyword)) {
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		// execute
		if (!$stmt->execute()) {
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		// bind result variables
		if (!$stmt->bind_result($venue, $date, $data)) {
			return error_log("Binding results failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		// fetch results
		while ($stmt->fetch()) {
			$return[] = [
				'venue' => $venue,
				'date' => $date,
				'data' => $data,
			];
		}
		$stmt->free_result();

		return $return;
	}

	public function get_stats() {
		$return = [];

		// prepare statement
		if (!($stmt = $this->db->prepare("
SELECT
	a.category, COALESCE(cnt_up, '-') AS 'cnt_up', COALESCE(cnt_down, '-') AS 'cnt_down', COALESCE(cnt_up / cnt_down, '-') AS 'ratio', COALESCE(cnt_up - cnt_down, cnt_up) AS 'diff'
FROM
	(SELECT category, COUNT(*) AS 'cnt_up' FROM foodVote WHERE vote = 'up' GROUP BY category) AS a
LEFT JOIN
	(SELECT category, COUNT(*) AS 'cnt_down' FROM foodVote WHERE vote = 'down' GROUP BY category) AS b
ON
	(a.category = b.category)
UNION ALL
	SELECT
		vote AS category, COALESCE(count(*), '-') AS 'cnt_up', '-' AS 'cnt_down', '-' AS 'ratio', COALESCE(count(*), '-') AS 'diff'
	FROM
		foodVote
	WHERE
		category = 'special'
	GROUP BY
		vote
ORDER BY
	diff DESC, ratio DESC
")))
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		// bind params
		/*if (!$stmt->bind_param("ss", $dataSourceKeyword, $dataKeyword))
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);*/
		// execute
		if (!$stmt->execute()) {
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		// bind result variables
		if (!$stmt->bind_result($category, $cnt_up, $cnt_down, $ratio, $diff)) {
			return error_log("Binding results failed: (" . $stmt->errno . ") " . $stmt->error);
		}
		// fetch results
		while ($stmt->fetch()) {
			$return[] = [
				'category' => $category,
				'cnt_up' => $cnt_up,
				'cnt_down' => $cnt_down,
				'ratio' => $ratio,
				'diff' => $diff,
			];
		}
		$stmt->free_result();

		return $return;
	}

}
