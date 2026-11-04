<?php

class JSON {
	static function encode($data) {
		$old_serialize_precision = ini_get('serialize_precision');
		ini_set('serialize_precision', 8);

		$data_json = json_encode($data);
		
		ini_set('serialize_precision', $old_serialize_precision);
		
		return $data_json;
	}
}
