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
		if (is_null(self::$instance))
			self::$instance = new self($timestamp);
		return self::$instance;
	}

	private function db_ok() {
		return ($this->db !== null);
	}

	public function saveToCache($dataSource, $date, $price, $data) {
		if (!$this->db_ok())
			return;
	
		$data = cleanText($data);

		if ($data && !empty($data)) {
			$timestamp = date('Y-m-d', $this->timestamp);
			// update 2013-07-23: use json instead of serialized data
			// because of better read- & editability
			//$priceDB = serialize($price);
			$priceDB = json_encode($price);

			// prepare statement
			if (!($stmt = $this->db->prepare("INSERT INTO foodCache VALUES (?, ?, ?, ?, ?)")))
				return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
			// bind params
			if (!$stmt->bind_param("sssss", $timestamp, $dataSource, $date, $priceDB, $data))
				return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
			// execute
			if (!$stmt->execute())
				return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
			$stmt->free_result();
		}
	}

	public function getFromCache($dataSource, &$date, &$price, &$data) {
		if (!$this->db_ok())
			return;
	
		$timestamp = date('Y-m-d', $this->timestamp);

		// prepare statement
		if (!($stmt = $this->db->prepare("SELECT date, price, data FROM foodCache WHERE timestamp=? AND dataSource=?")))
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		// bind params
		if (!$stmt->bind_param("ss", $timestamp, $dataSource))
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		// execute
		if (!$stmt->execute())
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		// bind result variables
		if (!$stmt->bind_result($date, $price, $data))
			return error_log("Binding results failed: (" . $stmt->errno . ") " . $stmt->error);
		// fetch results
		$result = $stmt->fetch();
		if ($result === false)
			return error_log("Fetching results failed: (" . $stmt->errno . ") " . $stmt->error);
		else if (!$result)
			return false;
		$stmt->free_result();

		// update 2013-07-23: use json instead of serialized data
		// because of better read- & editability
		// try json_decode first and if failed try unserialize
		// to be backwards compatible
		$price = json_decode($price, true);
		if ($price === NULL && !empty($price))
			$price = unserialize($price);
		$data_orig = $data;
		$data = cleanText($data);

		// update modidfied (cleaned) data
		if ($data != $data_orig) {
			$this->updateCache($dataSource, $date, $price, $data);
		};

		return true;
	}

	public function updateCache($dataSource, $date, $price, $data) {
		if (!$this->db_ok())
			return;

		$timestamp = date('Y-m-d', $this->timestamp);
		// update 2013-07-23: use json instead of serialized data
		// because of better read- & editability
		$priceDB = json_encode($price);

		// prepare statement
		if (!($stmt = $this->db->prepare("UPDATE foodCache SET date=?, price=?, data=? WHERE timestamp=? AND dataSource=?")))
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		// bind params
		if (!$stmt->bind_param("sssss", $timestamp, $dataSource, $date, $priceDB, $data))
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		// execute
		if (!$stmt->execute())
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		$stmt->free_result();
	}

	public function queryCache($dataSourceKeyword, $dataKeyword) {
		if (!$this->db_ok())
			return;

		$return = [];
		$dataSourceKeyword = "%${dataSourceKeyword}%";
		$dataKeyword = "%${dataKeyword}%";

		// prepare statement
		if (!($stmt = $this->db->prepare("SELECT timestamp, dataSource, data FROM foodCache WHERE dataSource LIKE ? AND data LIKE ?")))
			return error_log("Prepare failed: (" . $this->db->errno . ") " . $this->db->error);
		// bind params
		if (!$stmt->bind_param("ss", $dataSourceKeyword, $dataKeyword))
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		// execute
		if (!$stmt->execute())
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		// bind result variables
		if (!$stmt->bind_result($timestamp, $dataSource, $data))
			return error_log("Binding results failed: (" . $stmt->errno . ") " . $stmt->error);
		// fetch results
		while ($stmt->fetch()) {
			$return[] = [
				'timestamp'  => $timestamp,
				'dataSource' => $dataSource,
				'data'       => $data,
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
		if (!$stmt->execute())
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		// bind result variables
		if (!$stmt->bind_result($category, $cnt_up, $cnt_down, $ratio, $diff))
			return error_log("Binding results failed: (" . $stmt->errno . ") " . $stmt->error);
		// fetch results
		while ($stmt->fetch()) {
			$return[] = [
				'category' => $category,
				'cnt_up'   => $cnt_up,
				'cnt_down' => $cnt_down,
				'ratio'    => $ratio,
				'diff'     => $diff,
			];
		}
		$stmt->free_result();

		return $return;
	}

}
