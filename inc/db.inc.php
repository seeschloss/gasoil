<?php
class DB {
	static private $resource;

	function schema() {
		return [
			'pleins' => 'CREATE TABLE pleins (
				id INTEGER PRIMARY KEY,
				date TIMESTAMP,
				volume FLOAT,
				distance FLOAT,
				prix FLOAT,
				voiture TEXT,
				station TEXT,
				updated INTEGER,
				created INTEGER
			);',
			'notes' => 'CREATE TABLE notes (
				id INTEGER PRIMARY KEY,
				date TIMESTAMP,
				distance_absolue FLOAT,
				note TEXT,
				voiture TEXT
			);',
		];
	}

	function init_schema() {
		foreach ($this->schema() as $table) {
			$this->query($table);
		}
	}

	function __construct() {
		if (!isset(self::$resource)) {
			self::$resource = new PDO($GLOBALS['config']['database']['dsn']);

			$result = $this->query("SELECT COUNT(*) FROM pleins");
			if (!$result) {
				$this->init_schema();
			}
		}
	}

	function error() {
		$error = self::$resource->errorInfo();

		return is_array($error) ? $error[2] : "";
	}

	function query($query) {
		try {
			$result = self::$resource->query($query);
		} catch (PDOException $e) {
			$result = false;
		}

		if (!$result) {
			$error = $this->error();
			if (class_exists('Logger')) {
				Logger::error($error);
				Logger::error("Query was: ".$query);
			}
			else {
				trigger_error($error);
				trigger_error("Query was: ".$query);
			}
		}
		return $result;
	}

	function value($query) {
		$result = $this->query($query);
		if ($result) while ($row = $result->fetch()) {
			return $row[0];
		}

		return '';
	}

	function escape($string) {
		return self::$resource->quote($string);
	}

	function insert_id() {
		return self::$resource->lastInsertId();
	}
}

