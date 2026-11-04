<?php

class Alert extends Record {
	public static $table = "alerts";

	public $id = 0;
	public $unique_id = "";
	public $start = 0;
	public $stop = 0;
	public $longitude = 0.0;
	public $latitude = 0.0;
	public $distance = 150;
	public $contact_email = "";
	public $lang = "nl";
	public $last_email = 0;
	public $cancelled = 0;
	public $updated = 0;
	public $created = 0;

	function cancel_form() {
		return <<<HTML
	<form method="POST" action="">
		<button name="cancel" value="1">{$this->__("Cancel this alert")}</button>
	</form>
HTML;
	}

	function details_html() {
		$date_start = date("d/m/Y", $this->start);
		$date_stop = date("d/m/Y", $this->stop);

		$obfuscated_contact_email = preg_replace('/^(.).*(.@)/', '$1*****$2', $this->contact_email);

		return <<<HTML
	<dl>
		<dt>{$this->__("From")}</dt>
			<dd>{$date_start}</dd>
		<dt>{$this->__("To")}</dt>
			<dd>{$date_stop}</dd>
		<dt>{$this->__("Longitude")}</dt>
			<dd>{$this->longitude}</dd>
		<dt>{$this->__("Latitude")}</dt>
			<dd>{$this->latitude}</dd>
		<dt>{$this->__("Location")}</dt>
			<dd>{$this->readable_name()}</dd>
		<dt>{$this->__("Email address")}</dt>
			<dd>{$obfuscated_contact_email}</dd>
	</dl>
HTML;
	}

	function generate_unique_id() {
		$bytes = random_bytes(16);
		$unique_id = bin2hex($bytes);

		// check for collisions later, it's not like it really matters now

		$this->unique_id = $unique_id;
		return $this->unique_id;
	}
	
	function save() {
		return $this->id > 0 ? $this->update() : $this->insert();
	}

	function insert() {
		$db = new DB();

		$fields = [
			'unique_id' => $db->escape($this->unique_id),
			'start' => (int)$this->start,
			'stop' => (int)$this->stop,
			'longitude' => (float)$this->longitude,
			'latitude' => (float)$this->latitude,
			'distance' => (int)$this->distance,
			'contact_email' => $db->escape($this->contact_email),
			'lang' => $db->escape($this->lang),
			'cancelled' => 0,
			'last_email' => (int)$this->last_email,
			'created' => time(),
			'updated' => time(),
		];

		$query = 'INSERT INTO '.self::$table.' (' . implode(',', array_keys($fields)) . ') '.
		                               'VALUES (' . implode(',', array_values($fields)) . ')';

		$db->query($query);

		$this->id = $db->insert_id();

		return $this->id;
	}

	function update() {
		$db = new DB();

		$fields = [
			'unique_id' => $db->escape($this->unique_id),
			'start' => (int)$this->start,
			'stop' => (int)$this->stop,
			'longitude' => (float)$this->longitude,
			'latitude' => (float)$this->latitude,
			'distance' => (int)$this->distance,
			'contact_email' => $db->escape($this->contact_email),
			'lang' => $db->escape($this->lang),
			'cancelled' => (int)$this->cancelled,
			'last_email' => (int)$this->last_email,
			'updated' => time(),
		];

		$query = 'UPDATE '.self::$table.' SET ' . implode(', ', array_map(function($k, $v) { return $k . '=' . $v; }, array_keys($fields), $fields)) .
		         ' WHERE id = '.(int)$this->id;

		$db->query($query);

		return $this->id;
	}

	function delete() {
		$db = new DB();

		$query = 'DELETE FROM '.self::$table.' WHERE id = '.(int)$this->id;

		$db->query($query);

		return true;
	}

	function url() {
		return "{$GLOBALS['config']['base_path']}/alert/{$this->unique_id}";
	}
	
	function send_confirmation() {
		$old_lang = Lang::$lang;
		Lang::switch($this->lang);

		$date_start = date("d/m/Y", $this->start);
		$date_stop = date("d/m/Y", $this->stop);

		$subject = __("Alert confirmation for Antwerp parking bans");
		$text = <<<EOT
{$this->__("You just setup an alert for receiving a notification for new parking bans in Antwerp at the following location:")}

{$this->__("Longitude: %s", $this->longitude)}
{$this->__("Latitude: %s", $this->latitude)}
{$this->__("Location: %s", $this->readable_name())}

{$this->__("The alert will be valid from %s to %s, you can set a new one afterwards if you want.", $date_start, $date_stop)}


{$this->__("You can see these details and cancel this alert at the following address:")}
{$this->url()}


{$this->__("Send your remarks or enquiries to %s", $GLOBALS['config']['contact'])}
EOT;

		mail($this->contact_email, $subject, $text);
		$this->save();

		Lang::switch($old_lang);
	}
	
	function send_alert() {
		$old_lang = Lang::$lang;
		Lang::switch($this->lang);

		$date_start = date("d/m/Y", $this->start);
		$date_stop = date("d/m/Y", $this->stop);

		$bans = $this->bans();

		if (count($bans) == 0) {
			return true;
		}

		$bans_list = [];
		foreach ($bans as $ban) {
			$bans_list[] = $ban->details_text();
		}
		$bans_list = implode("\n\n", $bans_list);

		$subject = __("New parking bans in Antwerp");
		$text = <<<EOT
{$this->__("New parking bans:")}

{$bans_list}


{$this->__("You can cancel this alert at the following address:")}
{$this->url()}


{$this->__("Send your remarks or enquiries to %s", $GLOBALS['config']['contact'])}
EOT;

		mail($this->contact_email, $subject, $text);
		$this->last_email = time();
		$this->save();

		Lang::switch($old_lang);
	}

	function bans() {
		$bans = Parking_Ban::select_by_distance($this->longitude, $this->latitude, $this->distance);
		$active_bans = [];

		foreach ($bans as $ban) {
			if ($ban->updated >= $this->last_email and $ban->stop > time()) {
				$active_bans[] = $ban;
			}
		}

		return $active_bans;
	}

	function readable_name() {
		$url = "https://nominatim.openstreetmap.org/reverse?lat={$this->latitude}&lon={$this->longitude}&format=json";

		$http = new HTTP($url);
		$json = $http->get();

		if ($data = json_decode($json) and $data->display_name) {
			return $data->display_name;
		}

		return "(Unknown)";
	}
}
