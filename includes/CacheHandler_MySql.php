<?php

require_once('CacheHandler.php');

class CacheHandler_MySql extends CacheHandler {
	protected $db = null;

	function __construct($dataSource=null, $timestamp=null){
		$this->dataSource = $dataSource;
		$this->timestamp = $timestamp;

		// open db connection
		$this->db = new mysqli(DB_SERVER, DB_USER, DB_PASSWORD, DB_NAME);
		// PHP <= 5.3 compatibility
		if (/*$mysqli->connect_error*/mysqli_connect_error()) {
			error_log('Connect Error (' . /*$mysqli->connect_errno*/mysqli_connect_errno() . ') ' . /*$mysqli->connect_error*/mysqli_connect_error());
			die('Connect Error (' . /*$mysqli->connect_errno*/mysqli_connect_errno() . ') ' . /*$mysqli->connect_error*/mysqli_connect_error());
		}
		$this->db->query("SET NAMES 'utf8'");
	}

	public function saveToCache(&$date, &$price, &$data) {
		$data = cleanText($data);

		// avoid saving some data
		global $cacheDataDelete;
		if (stringsExist($data, $cacheDataDelete) !== false) {
			return;
		}

		if ($data && !empty($data)) {
			$dataSource = $this->dataSource;
			$timestamp = date('Y-m-d', $this->timestamp);
			// update 2013-07-23: use json instead of serialized data
			// because of better read- & editability
			//$priceDB = serialize($price);
			$priceDB = json_encode($price);
			// log timestamp year bug
			/*if (date('Y', $this->timestamp) !== date('Y')) {
				$host = $_SERVER['REMOTE_HOST'];
				$request = $_SERVER['REQUEST_URI'];
				error_log("[host $host] [request $request] [INSERT] POSSIBLE WRONG TIMESTAMP: $timestamp ON $dataSource, $data, $date, $price; DB NOT TOUCHED");
			}
			else*/
				$result = $this->db->query("INSERT INTO foodCache VALUES ('$timestamp', '$dataSource', '$date', '$priceDB', '$data')");
		}
	}

	public function getFromCache(&$date, &$price, &$data) {
		$dataSource = $this->dataSource;
		$timestamp = date('Y-m-d', $this->timestamp);
		global $cacheDataDelete;

		$result = $this->db->query("SELECT date, price, data FROM foodCache WHERE timestamp='$timestamp' AND dataSource='$dataSource'");
		if ($result && $result->num_rows == 1) {
			$row = $result->fetch_object();

			$date = $row->date;
			// update 2013-07-23: use json instead of serialized data
			// because of better read- & editability
			// try json_decode first and if failed try unserialize
			// to be backwards compatible
			$price = json_decode($row->price, true);
			if ($price === NULL && !empty($price))
				$price = unserialize($row->price);
			$data = cleanText($row->data);

			// get rid of unusable (e.g. free day, ..) data
			if (stringsExist($data, $cacheDataDelete) !== false) {
				$this->deleteFromCache($this->timestamp, $this->dataSource);
				return true;
			}

			// update modidfied (cleaned) data
			if ($data != $row->data) {
				$this->updateCache($date, $price, $data);
			};

			return true;
		}
		else {
			$data = null;
			return false;
		}
		return false;
	}

	public function updateCache($date, $price, $data) {
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

		// log timestamp year bug
		/*if (date('Y', $this->timestamp) !== date('Y')) {
			$host = $_SERVER['REMOTE_HOST'];
			$request = $_SERVER['REQUEST_URI'];
			error_log("[host $host] [request $request] [UPDATE] POSSIBLE WRONG TIMESTAMP: $timestamp ON $dataSource, $data, $date, $price; DB NOT TOUCHED");
		}
		else*/
			$result = $this->db->query("UPDATE foodCache SET date='$date', price='$priceDB', data='$data' WHERE timestamp='$timestamp' AND dataSource='$dataSource'");
	}

	public function queryCache($dataSourceKeyword, $dataKeyword, $selectors=array('timestamp', 'data')) {
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
		$this->db->query("DELETE FROM foodCache WHERE timestamp='$timestamp' AND dataSource='$dataSource'");
	}
}

?>