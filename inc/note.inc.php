<?php

class Note extends Record {
	public static $table = "notes";

	public $id = 0;
	public $date = 0;
	public $distance_absolue = 0;
	public $note = "";
	public $voiture = "";

	function date() {
		return (new DateTimeImmutable($this->date))->format("d/m/Y");
	}

	function grid_row($colspan) {
		if ($this->id) {
			return [
				'note' => [
					'value' => $this->date().' '.htmlspecialchars($this->note),
					'attributes' => ['colspan' => $colspan],
				],
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
			'distance_absolue' => (int)$this->distance_absolue,
			'note' => $db->escape($this->note),
			'voiture' => $db->escape($this->voiture),
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
			'date' => $db->escape($this->date),
			'distance_absolue' => (int)$this->distance_absolue,
			'note' => $db->escape($this->note),
			'voiture' => $db->escape($this->voiture),
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

	function form() {
		$form = new HTML_Form();
		$form->attributes['class'] = "dl-form";

		$form->fields['id'] = new HTML_Input("note-id");
		$form->fields['id']->type = "hidden";
		$form->fields['id']->name = "note[id]";
		$form->fields['id']->value = $this->id;

		$form->fields['date'] = new HTML_Input("note-date");
		$form->fields['date']->type = "datetime-local";
		$form->fields['date']->name = "note[date]";
		$form->fields['date']->value = $this->date;
		$form->fields['date']->label = __("Date");

		$form->fields['voiture_C4'] = new HTML_Input("note-voiture-C4");
		$form->fields['voiture_C4']->type = "radio";
		$form->fields['voiture_C4']->name = "note[voiture]";
		$form->fields['voiture_C4']->value = "C4";
		$form->fields['voiture_C4']->label = "C4";
		if (!$this->voiture or $this->voiture == "C4") {
			$form->fields['voiture_C4']->attributes = ['checked' => "checked"];
		}

		$form->fields['voiture_Picasso'] = new HTML_Input("note-voiture-Picasso");
		$form->fields['voiture_Picasso']->type = "radio";
		$form->fields['voiture_Picasso']->name = "note[voiture]";
		$form->fields['voiture_Picasso']->value = "Picasso";
		$form->fields['voiture_Picasso']->label = "Picasso";
		if ($this->voiture == "Picasso") {
			$form->fields['voiture_Picasso']->attributes = ['checked' => "checked"];
		}

		$form->fields['distance_absolue'] = new HTML_Input("note-distance-absolue");
		$form->fields['distance_absolue']->type = "number";
		$form->fields['distance_absolue']->name = "note[distance_absolue]";
		$form->fields['distance_absolue']->attributes['step'] = "1";
		$form->fields['distance_absolue']->value = (int)$this->distance_absolue;
		$form->fields['distance_absolue']->label = __("Distance (absolute)");

		$form->fields['note'] = new HTML_Textarea("note-note");
		$form->fields['note']->name = "note[note]";
		$form->fields['note']->value = htmlspecialchars($this->note);
		$form->fields['note']->label = __("Note");

		$form->actions['save'] = new HTML_Button("note-save");
		$form->actions['save']->type = "submit";
		$form->actions['save']->name = "action";
		$form->actions['save']->label = __("Save");

		if ($this->id > 0) {
			$form->actions['save']->value = "update";

			$form->actions['delete'] = new HTML_Button_Confirm("note-delete");
			$form->actions['delete']->name = "action";
			$form->actions['delete']->label = __("Delete");
			$form->actions['delete']->value = "delete";
			$form->actions['delete']->confirmation = __("Are you sure you want to delete this note?");
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

		if (isset($data['voiture'])) {
			$this->voiture = $data['voiture'];
		}

		if (isset($data['distance_absolue'])) {
			$this->distance_absolue = (int)$data['distance_absolue'];
		}

		if (isset($data['note'])) {
			$this->note = $data['note'];
		}
	}
}
