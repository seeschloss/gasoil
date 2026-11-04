<?php

class Cron {
	public $timestamp;

	function __construct($timestamp) {
		$this->timestamp = $timestamp;
	}

	function execute() {
		foreach (Alert::select() as $alert) {
			$alert->check();
		}
	}
}
