<?php

require_once('includes.php');

abstract class CacheHandler {
	protected $dataSource = null;
	protected $timestamp = null;
	
	abstract public function saveToCache(&$date, &$price, &$data);
	abstract public function getFromCache(&$date, &$price, &$data);
	abstract public function queryCache($dataSourceKeyword, $dataKeyword, $selectors=array('timestamp', 'data'));
	abstract public function deleteFromCache($timestamp, $dataSource);
}

?>
