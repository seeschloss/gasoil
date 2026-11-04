<?php

class Update {
	static function perform() {
		$db = new DB();

		if (!$db->query('SELECT id FROM pleins')) {
			self::to_1();
		}

		if (!$db->query('SELECT distance_absolue FROM pleins')) {
			self::to_2();
		}

		if (!$db->query('SELECT fuel FROM pleins')) {
			self::to_3();
		}

		if ($db->value("SELECT COUNT(*) FROM pleins WHERE distance_absolue IS NULL") > 0) {
			self::to_4();
		}

		if (!$db->query('SELECT plein FROM pleins')) {
			self::to_5();
		}

		if (!$db->query('SELECT id FROM notes')) {
			self::to_6();
		}

		echo "All done.\n";
	}

	static function to_6() {
		echo "6. Create table `notes`\n";

		$db = new DB();
		$db->query('CREATE TABLE notes (
			id INTEGER PRIMARY KEY,
			date TIMESTAMP,
			distance_absolue FLOAT,
			note TEXT,
			voiture TEXT
		)');

		if ($db->error()) {
			print $db->error()."\n";
		}
	}

	static function to_5() {
		echo "5. Add `plein` to table `pleins`\n";

		$db = new DB();
		$db->query('ALTER TABLE pleins ADD plein BOOLEAN');
		$db->query("UPDATE pleins SET plein=TRUE WHERE fuel <> 'XTO'");
		if ($db->error()) {
			print $db->error()."\n";
		}
	}

	static function to_4() {
		echo "4. Update `distance_absolue` and add `distance_absolue_calculated` in table `pleins`\n";

		$db = new DB();
		$db->query('ALTER TABLE pleins ADD distance_absolue_calculated BOOLEAN');
		if ($db->error()) {
			print $db->error()."\n";
		}

		$distances_absolues_per_car = [];

		$pleins = Plein::select([], 'date DESC');

		foreach ($pleins as $plein) {
			if ($plein->distance_absolue and !$plein->distance_absolue_calculated) {
				$distances_absolues_per_car[$plein->voiture] = $plein->distance_absolue - $plein->distance;
			} else {
				if (isset($distances_absolues_per_car[$plein->voiture])) {
					$plein->distance_absolue = $distances_absolues_per_car[$plein->voiture];
					$plein->distance_absolue_calculated = true;
					$plein->update();

					$distances_absolues_per_car[$plein->voiture] -= $plein->distance;
				}
			}
		}
	}

	static function to_3() {
		echo "3. Add `fuel` to table `pleins`\n";

		$db = new DB();
		$db->query('ALTER TABLE pleins ADD fuel TEXT');
		$db->query("UPDATE pleins SET fuel='B7' WHERE voiture <> 'Picasso'");
		$db->query("UPDATE pleins SET fuel='E10' WHERE voiture = 'Picasso'");
		if ($db->error()) {
			print $db->error()."\n";
		}
	}

	static function to_2() {
		echo "2. Add `distance_absolue` to table `pleins`\n";

		$db = new DB();
		$db->query('ALTER TABLE pleins ADD distance_absolue INTEGER DEFAULT NULL');
		if ($db->error()) {
			print $db->error()."\n";
		}
	}

	static function to_1() {
		echo "1. Add id to table `pleins`\n";

		$db = new DB();
		$db->query('CREATE TABLE pleins_new (
			id INTEGER PRIMARY KEY,
			date TIMESTAMP,
			volume FLOAT,
			distance FLOAT,
			prix FLOAT,
			voiture TEXT,
			station TEXT
		)');

		$db->query('INSERT INTO pleins_new (date, volume, distance, prix, voiture, station)
			SELECT * FROM pleins');
		$db->query('DROP TABLE pleins');
		$db->query('ALTER TABLE pleins_new RENAME TO pleins');
		if ($db->error()) {
			print $db->error()."\n";
		}
	}
}
