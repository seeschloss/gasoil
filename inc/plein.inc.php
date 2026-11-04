<?php

class Plein extends Record {
	public static $table = "pleins";

	public $id = 0;
	public $date = 0;
	public $volume = 0;
	public $distance = 0;
	public $distance_absolue = 0;
	public $distance_absolue_calculated = false;
	public $prix = 0;
	public $voiture = "";
	public $fuel = "";
	public $station = "";
	public $plein = true;
	public $created = 0;
	public $updated = 0;

	static function filter($filters, $order = NULL) {
		$fields = [];

		return self::select($fields, $order);
	}

	static function filters() {
		$filters = [];

		return $filters;
	}

	function date() {
		return (new DateTimeImmutable($this->date))->format("d/m/Y");
	}

	function formatted_distance() {
		return Format::number($this->distance ?: $this->distance_difference(), 0).' Km';
	}

	function volume() {
		return Format::number($this->volume, 0).' L';
	}

	function prix() {
		return Format::number($this->prix, 3).' €/L';
	}

	function amount() {
		return Format::number($this->prix * $this->volume, 2).' €';
	}

	function conso($previous_plein) {
		if (!$previous_plein) {
			return '-';
		}

		if (!$this->plein) {
			$volume = $previous_plein->volume;
		} else if (!$previous_plein->plein) {
			$volume = $previous_plein->volume;
		} else {
			$volume = $this->volume;
		}

		return $this->distance_difference() ? Format::number($volume / $this->distance_difference() * 100, 2).' L<i>/100 Km</i>' : "?";
	}

	function price_distance($previous_plein) {
		if (!$this->plein) {
			$volume = $previous_plein->volume;
		} else if (!$previous_plein->plein) {
			$volume = $previous_plein->volume;
		} else {
			$volume = $this->volume;
		}

		return $this->distance_difference() ? Format::number($previous_plein->prix * $volume / $this->distance_difference() * 100, 2).' €<i>/100 Km</i>' : "?";
	}

	function formatted_distance_absolue() {
		return ($this->distance_absolue_calculated ? '*' : '').Format::number($this->distance_absolue, 0).' Km';
	}

	function distance_difference() {
		$previous_plein_same_car = array_first(Plein::select(['voiture' => $this->voiture, "date < '".$this->date."'"], 'date DESC', 1));

		if ($previous_plein_same_car) {
			if ($previous_plein_same_car->distance_absolue > 0 and $this->distance_absolue > 0) {
				return $this->distance_absolue - $previous_plein_same_car->distance_absolue;
			} else {
				return $this->distance;
			}
		}

		return $this->distance;
	}

	function distance_since_last_plein() {
		$previous_plein_same_car = array_first(Plein::select(['voiture' => $this->voiture, 'plein' => 1, "date < '".$this->date."'"], 'date DESC', 1));

		if ($previous_plein_same_car) {
			$previous_not_pleins = Plein::select(['voiture' => $this->voiture, 'plein' => '0', "date < '".$this->date."'", "date >= '".$previous_plein_same_car->date."'"], 'date DESC');
			if (count($previous_not_pleins)) {
				debug::dump($previous_not_pleins);
			}

			return array_sum(array_map(fn($plein) => $plein->distance, $previous_not_pleins)) + $previous_plein_same_car->distance;
		}

		return $this->distance;
	}

	static function grid_row_header() {
		return [
			'date' => __('Date'),
			'distance' => __('Distance'),
			'volume' => __('Volume'),
			'montant' => __('Amount'),
			'prix' => __('Price'),
			'conso' => __('Conso'),
			'price-distance' => __('Price/distance'),
		];
	}

	function grid_row() {
		if ($this->id) {
			return [
				'date' => $this->date(),
				'distance' => "",
				'volume' => $this->volume(),
				'montant' => [
					'value' => $this->amount(),
					'attributes' => ['title' => $this->station],
				],
				'prix' => $this->prix(),
				'conso' => "",
				'price-distance' => "",
			];
		}
	}

	function grid_row_interval($previous) {
		if ($this->id) {
			return [
				'date' => "",
				'distance' => [
					'value' => $this->distance_since_last_plein(),
					'attributes' => ['title' => $this->formatted_distance_absolue()],
				],
				'volume' => "",
				'montant' => "",
				'prix' => "",
				'conso' => $this->conso($previous),
				'price-distance' => $previous ? $this->price_distance($previous) : "-",
			];
		}
	}

	function grid_row_single($previous) {
		if ($this->id) {
			return [
				'date' => $this->date(),
				'distance' => $this->formatted_distance(),
				'volume' => $this->volume(),
				'montant' => $this->amount(),
				'prix' => $this->prix(),
				'conso' => $this->conso($previous),
				'price-distance' => $previous ? $this->price_distance($previous) : "-",
				'voiture' => $this->voiture,
			];
		}
	}

	function save() {
		return $this->id > 0 ? $this->update() : $this->insert();
	}

	function insert() {
		$db = new DB();

		$fields = [
			'date' => $db->escape($this->date),
			'volume' => (float)$this->volume,
			'distance' => (float)$this->distance,
			'distance_absolue' => (int)$this->distance_absolue,
			'distance_absolue_calculated' => (int)$this->distance_absolue_calculated,
			'prix' => (float)$this->prix,
			'voiture' => $db->escape($this->voiture),
			'fuel' => $db->escape($this->fuel),
			'station' => $db->escape($this->station),
			'plein' => (int)$this->plein,
			'created' => time(),
			'updated' => time(),
		];

		$query = 'INSERT INTO pleins (' . implode(',', array_keys($fields)) . ') '.
		                     'VALUES (' . implode(',', array_values($fields)) . ')';
		$db->query($query);

		$this->id = $db->insert_id();

		return $this->id;
	}

	function update() {
		$db = new DB();

		$fields = [
			'date' => $db->escape($this->date),
			'volume' => (float)$this->volume,
			'distance' => (float)$this->distance,
			'distance_absolue' => (int)$this->distance_absolue,
			'distance_absolue_calculated' => (int)$this->distance_absolue_calculated,
			'prix' => (float)$this->prix,
			'voiture' => $db->escape($this->voiture),
			'fuel' => $db->escape($this->fuel),
			'station' => $db->escape($this->station),
			'plein' => (int)$this->plein,
			'updated' => time(),
		];

		$query = 'UPDATE pleins SET ' . implode(', ', array_map(function($k, $v) { return $k . '=' . $v; }, array_keys($fields), $fields)) .
		         ' WHERE id = '.(int)$this->id;

		$db->query($query);

		return $this->id;
	}

	function delete() {
		$db = new DB();

		$query = 'DELETE FROM pleins WHERE id = '.(int)$this->id;

		$db->query($query);

		return true;
	}

	function form() {
		$form = new HTML_Form();
		$form->attributes['class'] = "dl-form";

		$form->fields['id'] = new HTML_Input("plein-id");
		$form->fields['id']->type = "hidden";
		$form->fields['id']->name = "plein[id]";
		$form->fields['id']->value = $this->id;

		$form->fields['date'] = new HTML_Input("plein-date");
		$form->fields['date']->type = "datetime-local";
		$form->fields['date']->name = "plein[date]";
		$form->fields['date']->value = $this->date;
		$form->fields['date']->label = __("Date");

		$form->fields['volume'] = new HTML_Input("plein-volume");
		$form->fields['volume']->type = "number";
		$form->fields['volume']->name = "plein[volume]";
		$form->fields['volume']->attributes['step'] = "0.01";
		$form->fields['volume']->value = $this->volume;
		$form->fields['volume']->label = __("Volume");

		$form->fields['distance'] = new HTML_Input("plein-distance");
		$form->fields['distance']->type = "number";
		$form->fields['distance']->name = "plein[distance]";
		$form->fields['distance']->attributes['step'] = "0.1";
		$form->fields['distance']->value = $this->distance;
		$form->fields['distance']->label = __("Distance");

		$form->fields['distance-absolue'] = new HTML_Input("plein-distance-absolue");
		$form->fields['distance-absolue']->type = "number";
		$form->fields['distance-absolue']->name = "plein[distance-absolue]";
		$form->fields['distance-absolue']->attributes['step'] = "1";
		$form->fields['distance-absolue']->value = $this->distance_absolue;
		$form->fields['distance-absolue']->label = __("Distance (absolute)");

		$form->fields['prix'] = new HTML_Input("plein-prix");
		$form->fields['prix']->type = "number";
		$form->fields['prix']->name = "plein[prix]";
		$form->fields['prix']->value = $this->prix;
		$form->fields['prix']->attributes['step'] = "0.001";
		$form->fields['prix']->label = __("Prix au litre");

		$form->fields['voiture_C4'] = new HTML_Input("plein-voiture-C4");
		$form->fields['voiture_C4']->type = "radio";
		$form->fields['voiture_C4']->attributes = ['checked' => "checked"];
		$form->fields['voiture_C4']->name = "plein[voiture]";
		$form->fields['voiture_C4']->value = "C4";
		$form->fields['voiture_C4']->label = "C4";

		$form->fields['voiture_Picasso'] = new HTML_Input("plein-voiture-Picasso");
		$form->fields['voiture_Picasso']->type = "radio";
		$form->fields['voiture_Picasso']->name = "plein[voiture]";
		$form->fields['voiture_Picasso']->value = "Picasso";
		$form->fields['voiture_Picasso']->label = "Picasso";

		$form->fields['fuel'] = new HTML_Select("plein-fuel");
		$form->fields['fuel']->type = "radio";
		$form->fields['fuel']->name = "plein[fuel]";
		$form->fields['fuel']->label = __("Fuel");
		$form->fields['fuel']->options = [
			'B7' => 'Gasoil',
			'SP95-E10' => 'SP95-E10',
			'SP98-E5' => 'SP98-E5',
			'E85' => 'E85',
			'XTO' => 'HVO',
		];
		$form->fields['fuel']->suffix = <<<JS
<script>
document.querySelector("#plein-voiture-Picasso").onchange = function() {
	if (this.checked) { document.querySelector("#plein-fuel").value = "SP95-E10"; }
};
document.querySelector("#plein-voiture-C4").onchange = function() {
	if (this.checked) { document.querySelector("#plein-fuel").value = "B7"; }
};
</script>
JS;

		$form->fields['station'] = new HTML_Input("plein-station");
		$form->fields['station']->type = "text";
		$form->fields['station']->name = "plein[station]";
		$form->fields['station']->value = $this->station;
		$form->fields['station']->label = __("Station");

		$form->fields['plein'] = new HTML_Input_Checkbox("plein-plein");
		$form->fields['plein']->name = "plein[plein]";
		$form->fields['plein']->value = $this->plein;
		$form->fields['plein']->label = __("Full");

		$form->actions['save'] = new HTML_Button("plein-save");
		$form->actions['save']->type = "submit";
		$form->actions['save']->name = "action";
		$form->actions['save']->label = __("Save");

		if ($this->id > 0) {
			$form->actions['save']->value = "update";

			$form->actions['delete'] = new HTML_Button_Confirm("plein-delete");
			$form->actions['delete']->name = "action";
			$form->actions['delete']->label = __("Delete");
			$form->actions['delete']->value = "delete";
			$form->actions['delete']->confirmation = __("Are you sure you want to delete this plein?");
		} else {
			$form->actions['save']->value = "insert";
		}

		return $form->html();
	}

	function from_form($data) {
		if (isset($data['id'])) {
			$this->id = (int)$data['id'];

			if ($this->id) {
				$this->load(['id' => $this->id]);
			}
		}

		if (isset($data['date'])) {
			$this->date = $data['date'];
		}

		if (isset($data['volume'])) {
			$this->volume = (float)$data['volume'];
		}

		if (isset($data['distance'])) {
			$this->distance = (float)$data['distance'];
		}

		if (isset($data['distance-absolue'])) {
			$this->distance_absolue = (int)$data['distance-absolue'];
		}

		if (isset($data['prix'])) {
			$this->prix = (float)$data['prix'];
		}

		if (isset($data['voiture'])) {
			$this->voiture = $data['voiture'];
		}

		if (isset($data['fuel'])) {
			$this->fuel = $data['fuel'];
		}

		if (isset($data['station'])) {
			$this->station = $data['station'];
		}

		if (isset($data['plein'])) {
			$this->plein = true;
		} else {
			$this->plein = false;
		}
	}
}

