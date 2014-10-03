<?php

require_once(__DIR__ . '/CacheHandler.php');

class CacheHandler_MySql extends CacheHandler {
	protected $db = null;

	function __construct($dataSource=null, $timestamp=null) {
		$this->dataSource = $dataSource;
		$this->timestamp = $timestamp;

		// open db connection, try different configs after each other if connection fails
		global $DB_CONFIGS;
		$this->db = mysqli_init();
		if (!$this->db->options(MYSQLI_OPT_CONNECT_TIMEOUT, 3)) {
			return error_log('Options Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
		}
		$db_connect_ok = false;
		foreach ($DB_CONFIGS as $db_config) {
			if (!$this->db->real_connect($db_config['DB_SERVER'], $db_config['DB_USER'], $db_config['DB_PASSWORD'], $db_config['DB_NAME']))
				error_log('Connect Error (' . mysqli_connect_errno() . ') ' . mysqli_connect_error());
			else {
				$db_connect_ok = true;
				break;
			}
		}
		if (!$db_connect_ok) {
			$this->db = null;
			return;
		}
		$this->db->query("SET NAMES 'utf8'");
	}

	public function saveToCache(&$date, &$price, &$data) {
		// abort on error
		if (!$this->db)
			return null;

		$data = cleanText($data);

		// avoid saving some data
		global $cacheDataDelete;
		if (stringsExist($data, $cacheDataDelete) !== false)
			return;

		if ($data && !empty($data)) {
			$dataSource = $this->dataSource;
			$timestamp = date('Y-m-d', $this->timestamp);
			// update 2013-07-23: use json instead of serialized data
			// because of better read- & editability
			//$priceDB = serialize($price);
			$priceDB = json_encode($price);

			// prepare statement
			if (!($stmt = $this->db->prepare("INSERT INTO foodCache VALUES (?, ?, ?, ?, ?)")))
				return error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
			// bind params
			if (!$stmt->bind_param("sssss", $timestamp, $dataSource, $date, $priceDB, $data))
				return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
			// execute
			if (!$stmt->execute())
				return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
		}
	}

	public function getFromCache(&$date, &$price, &$data) {
		// abort on error
		if (!$this->db)
			return null;

		$dataSource = $this->dataSource;
		$timestamp = date('Y-m-d', $this->timestamp);
		global $cacheDataDelete;

		// prepare statement
		if (!($stmt = $this->db->prepare("SELECT date, price, data FROM foodCache WHERE timestamp=? AND dataSource=?")))
			return error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
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

		// update 2013-07-23: use json instead of serialized data
		// because of better read- & editability
		// try json_decode first and if failed try unserialize
		// to be backwards compatible
		$price = json_decode($price, true);
		if ($price === NULL && !empty($price))
			$price = unserialize($price);
		$data_orig = $data;
		$data = cleanText($data);

		// get rid of unusable (e.g. free day, ..) data
		if (stringsExist($data, $cacheDataDelete) !== false) {
			$this->deleteFromCache($this->timestamp, $this->dataSource);
			return true;
		}

		// update modidfied (cleaned) data
		if ($data != $data_orig) {
			$this->updateCache($date, $price, $data);
		};

		return true;
	}

	public function updateCache($date, $price, $data) {
		// abort on error
		if (!$this->db)
			return null;

		$dataSource = $this->dataSource;
		$timestamp = date('Y-m-d', $this->timestamp);
		// update 2013-07-23: use json instead of serialized data
		// because of better read- & editability
		$priceDB = json_encode($price);

		// avoid saving some data
		global $cacheDataDelete;
		if (stringsExist($data, $cacheDataDelete) !== false) {
			return;
		}

		// prepare statement
		if (!($stmt = $this->db->prepare("UPDATE foodCache SET date=?, price=?, data=? WHERE timestamp=? AND dataSource=?")))
			return error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
		// bind params
		if (!$stmt->bind_param("sssss", $date, $price, $timestamp, $dataSource))
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		// execute
		if (!$stmt->execute())
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
	}

	public function queryCache($dataSourceKeyword, $dataKeyword, $selectors=array('timestamp', 'data')) {
		// abort on error
		if (!$this->db)
			return null;

		$return = array();

		$selector = implode(', ', $selectors);
		$result = $this->db->query("SELECT $selector FROM foodCache WHERE dataSource LIKE '%$dataSourceKeyword%' AND data LIKE '%$dataKeyword%'");
		while ($row = $result->fetch_object()) {
			$returnRow = array();
			foreach ($selectors as $sel) {
				$returnRow[$sel] = $row->$sel;
			}
			$return[] = $returnRow;
		}
		return $return;
	}

	public function deleteFromCache($timestamp, $dataSource) {
		// abort on error
		if (!$this->db)
			return null;

		// prepare statement
		if (!($stmt = $this->db->prepare("DELETE FROM foodCache WHERE timestamp=? AND dataSource=?")))
			return error_log("Prepare failed: (" . $mysqli->errno . ") " . $mysqli->error);
		// bind params
		if (!$stmt->bind_param("ss", $timestamp, $dataSource))
			return error_log("Binding parameters failed: (" . $stmt->errno . ") " . $stmt->error);
		// execute
		if (!$stmt->execute())
			return error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
	}
}

?>
