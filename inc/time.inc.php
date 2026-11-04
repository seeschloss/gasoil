<?php

class Time {
	public static function period($timestamp) {
		$sunrise = date_sunrise($timestamp,
				SUNFUNCS_RET_TIMESTAMP,
				$GLOBALS['config']['location']['latitude'],
				$GLOBALS['config']['location']['longitude'],
				91);

		if ($timestamp < $sunrise) {
			return 'night';
		}

		$twilight_stop = date_sunrise($timestamp,
				SUNFUNCS_RET_TIMESTAMP,
				$GLOBALS['config']['location']['latitude'],
				$GLOBALS['config']['location']['longitude'],
				80);

		if ($timestamp < $twilight_stop) {
			return 'twilight';
		}

		$twilight_start = date_sunset($timestamp,
				SUNFUNCS_RET_TIMESTAMP,
				$GLOBALS['config']['location']['latitude'],
				$GLOBALS['config']['location']['longitude'],
				80);

		if ($timestamp < $twilight_start) {
			return 'day';
		}

		$sunset = date_sunset($timestamp,
				SUNFUNCS_RET_TIMESTAMP,
				$GLOBALS['config']['location']['latitude'],
				$GLOBALS['config']['location']['longitude'],
				91);

		if ($timestamp < $sunset) {
			return 'twilight';
		}

		return 'night';
	}
}

