<?php

class HTML_Input extends HTML_Form_Element {
	public $type = "text";

	public function element() {
		$html = '<input ';

		$attributes = $this->attributes;

		$attributes['id'] = $this->id;
		$attributes['name'] = $this->name;
		$attributes['type'] = $this->type;
		$attributes['value'] = $this->value;

		foreach ($attributes as $name => $value) {
			$html .= $name.'="'.$value.'" ';
		}

		$html .= '/>';

		return $html.$this->suffix();
	}
}

class HTML_Input_Checkbox extends HTML_Input {
	public $type = "checkbox";

	public function element() {
		$html = "";

		if ($this->value) {
			$input_hidden = new HTML_Input($this->id.'-hidden');
			$input_hidden->type = "hidden";
			$input_hidden->value = 0;
			$input_hidden->name = $this->name;
			$html = $input_hidden->element();
		}

		$html .= '<input ';

		$attributes = $this->attributes;

		$attributes['id'] = $this->id;
		$attributes['name'] = $this->name;
		$attributes['type'] = $this->type;
		$attributes['value'] = "1";

		if ($this->value) {
			$attributes['checked'] = "checked";
		}

		foreach ($attributes as $name => $value) {
			$html .= $name.'="'.$value.'" ';
		}

		$html .= '/>';

		return $html.$this->suffix();
	}
}

class HTML_Input_Datetime extends HTML_Input {
	public $type = "date";

	public function element() {
		$html = "";

		$html .= '<input ';

		$attributes = $this->attributes;

		$attributes['id'] = $this->id;
		$attributes['name'] = $this->name;
		$attributes['type'] = "date";

		if ($this->value) {
			$attributes['value'] = gmdate("Y-m-d", $this->value);
		}

		foreach ($attributes as $name => $value) {
			$html .= $name.'="'.$value.'" ';
		}

		$html .= '/>';

		$html .= '<input ';

		$attributes = $this->attributes;

		$attributes['id'] = $this->id."-time";
		$attributes['name'] = $this->name."-time";
		$attributes['type'] = "time";

		if ($this->value) {
			$attributes['value'] = gmdate("H:i:s", $this->value);
		}

		foreach ($attributes as $name => $value) {
			$html .= $name.'="'.$value.'" ';
		}

		$html .= '/>';

		return $html.$this->suffix();
	}
}

class HTML_Input_Color extends Html_Input {
	public $type = "color";
}

class HTML_Button extends HTML_Form_Element {
	public $type = "submit";

	public function element() {
		$html = '<button ';

		$attributes = $this->attributes;

		$attributes['id'] = $this->id;
		$attributes['name'] = $this->name;
		$attributes['type'] = $this->type;
		$attributes['value'] = $this->value;

		foreach ($attributes as $name => $value) {
			$html .= $name.'="'.$value.'" ';
		}

		$html .= '>'.$this->label.'</button>';
		return $html.$this->suffix();
	}

	public function html($label = '') {
		return $this->element();
	}
}

class HTML_Button_Confirm extends HTML_Button {
	public $confirmation = "Are you sure?";

	public function element() {
		$html = '<button ';

		$attributes = $this->attributes;

		$attributes['id'] = $this->id;
		$attributes['name'] = $this->name;
		$attributes['type'] = $this->type;
		$attributes['value'] = $this->value;

		$attributes['onclick'] = "return confirm('{$this->confirmation}')";

		foreach ($attributes as $name => $value) {
			$html .= $name.'="'.$value.'" ';
		}

		$html .= '>'.$this->label.'</button>';
		return $html;
	}
}

class HTML_Textarea extends HTML_Form_Element {
	public function element() {
		$html = '<textarea ';

		$attributes = $this->attributes;

		$attributes['id'] = $this->id;
		$attributes['name'] = $this->name;

		foreach ($attributes as $name => $value) {
			$html .= $name.'="'.$value.'" ';
		}

		$html .= '>'.$this->value.'</textarea>';
		return $html.$this->suffix();
	}
}

class HTML_Select extends HTML_Form_Element {
	public $options = [];

	public $attributes = [];

	public function __construct($id) {
		$this->id = $id;
	}

	public function error($message) {
		return '<span class="error">'.$message.'</span>';
	}

	public function options() {
		$elements = array_map(function($key, $value) {
			$selected = '';
			if ($key == $this->value) {
				$selected = ' selected="selected"';
			} else if (is_array($this->value) and array_search($key, $this->value) !== FALSE) {
				$selected = ' selected="selected"';
			}

			$attributes = [];

			if (!is_array($value)) {
				$title = $value;
			} else {
				$title = $value['title'];

				foreach ($value as $k => $v) {
					if (strpos($k, 'data-') === 0) {
						$attributes[] = "$k='$v'";
					}
				}
			}

			$attributes = implode(' ', $attributes);

			return '<option value="'.$key.'" '.$selected.' '.$attributes.'>'.$title.'</option>';
		}, array_keys($this->options), $this->options);

		return implode('', $elements);
	}

	public function element() {
		$html = '<select ';

		$attributes = $this->attributes;

		$attributes['id'] = $this->id;
		$attributes['name'] = $this->name;

		foreach ($attributes as $name => $value) {
			$html .= $name.'="'.$value.'" ';
		}

		$html .= '>';

		$html .= $this->options();

		$html .= '</select>';
		return $html.$this->suffix();
	}
}

class HTML_Table {
	public $title = "";
	public $filters = [];
	public $header = [];
	public $rows = [];

	public function html() {
		$header_html = join("", array_map(function($key, $field) {
			return "<th data-key='{$key}'>{$field}</th>";
		}, array_keys($this->header), $this->header));

		$rows_html = array_reduce($this->rows, function($html, $row) {
			if (!isset($row['cells'])) {
				$row = [
					'cells' => $row,
				];
			}

			$attributes = [];
			if (isset($row['attributes'])) {
				$attributes += $row['attributes'];
			}

			$attributes = join(" ", array_map(function($key, $value) {
				return "{$key}='{$value}'";
			}, array_keys($attributes), $attributes));

			$row_html = "<tr {$attributes}>";

			foreach ($row['cells'] as $key => $field) {
				if (!is_array($field)) {
					$field = [
						'value' => $field,
					];
				}
				
				$attributes = [
					'data-key' => $key,
				];
				if (isset($field['attributes'])) {
					$attributes += $field['attributes'];
				}

				$attributes = join(" ", array_map(function($key, $value) {
					return "{$key}='{$value}'";
				}, array_keys($attributes), $attributes));

				$row_html .= "<td {$attributes}>{$field['value']}</td>";
			}

			$row_html .= "</tr>";
			return $html.$row_html;
		}, "");

		$filters = "";
		if (count($this->filters)) {
			$form = new HTML_Form();
			$form->attributes = ['class' => 'filters'];
			$form->fields = $this->filters;
			$form->method = "GET";

			$form->actions['search'] = new HTML_Button("filter-submit");
			$form->actions['search']->name = "action";
			$form->actions['search']->label = __("Filter");
			$form->actions['search']->value = "filter";

			$filters .= $form->html();
		}

		$html = <<<HTML
	<table>
		<caption>{$this->title}
			{$filters}
		</caption>
		<thead></thead>
		<tbody>
			<tr>{$header_html}</tr>
			{$rows_html}
		</tbody>
	</table>
HTML;

		return $html;
	}
}


class HTML_Form_Element {
	public $id = "";
	public $name = "";
	public $value = "";
	public $label = "";

	public $suffix = "";

	public $attributes = [];

	public function __construct($id) {
		$this->id = $id;
	}

	public function label($label = null) {
		if ($label === null) {
			$label = $this->label;
		}

		return '<label for="'.$this->id.'">'.$label.'</label>';
	}

	public function error($message) {
		return '<span class="error">'.$message.'</span>';
	}

	public function suffix() {
		if ($this->suffix) {
			return "<span class='form-element-suffix'>{$this->suffix}</span>";
		} else {
			return "";
		}
	}

	public function element() {
		return $this->suffix();
	}

	public function html($label = '') {
		$html = '';

		if ($label) {
			$html .= $this->label($label);
		}

		$html .= $this->element();

		if (!empty($GLOBALS['form_errors'][$this->name])) {
			$html .= $this->error($GLOBALS['form_errors'][$this->name]);
		}

		return $html;
	}
}

class HTML_Form {
	public $target = "";
	public $method = "POST";
	public $fields = [];
	public $parameters = [];
	public $actions = [];
	public $attributes = [];
	public $enctype = "";

	public function html() {
		$fields_html = array_reduce($this->fields, function($html, $field) {
			if ($field instanceof HTML_Form_Element) {
				$field_html = "<dt id='field-{$field->id}'>{$field->label()}</dt><dd>{$field->element()}</dd>";

				if (isset($field->type) and $field->type == "file") {
					$this->enctype = "multipart/form-data";
				}
			} else if (is_array($field)) {
				$field_html = "<dt>{$field['label']}</dt><dd>{$field['value']}</dd>";
			}

			return $html.$field_html;
		}, "");

		$parameters_html = array_reduce($this->parameters, function($html, $parameter) {
			if ($parameter instanceof HTML_Form_Element) {
				$parameter_html = "<dt>{$parameter->label()}</dt><dd>{$parameter->element()}</dd>";
			} else if (is_array($parameter)) {
				$parameter_html = "<dt>{$parameter['label']}</dt><dd>{$parameter['value']}</dd>";
			}

			return $html.$parameter_html;
		}, "");
		if ($parameters_html) {
			$parameters_html = "<dl class='form-parameters'>{$parameters_html}</dl>";
		}

		$actions_html = array_reduce($this->actions, function($html, $action) {
			$action_html = $action->html();

			return $html.$action_html;
		}, "");

		$attributes = join(" ", array_map(function($key, $value) {
			return "{$key}='{$value}'";
		}, array_keys($this->attributes), $this->attributes));

		$html = <<<HTML
	<form target="{$this->target}" method="{$this->method}" enctype="{$this->enctype}" {$attributes}>
		<dl class='form-fields'>{$fields_html}</dl>
		{$parameters_html}
		<span class="actions">{$actions_html}</span>
	</form>
HTML;

		return $html;
	}
}

class HTML_DL {
	public $elements = [];

	public function html() {
		return '<dl>'.join('', array_map(function($element) {
			$attributes = "";

			if (isset($element['attributes'])) {
				$attributes = join(" ", array_map(function($key, $value) {
					return "{$key}='{$value}'";
				}, array_keys($element['attributes']), $element['attributes']));
			}

			return <<<HTML
				<dt $attributes>{$element['title']}</dt>
				<dd>{$element['value']}</dd>
HTML;
		}, $this->elements)).'</dl>';
	}
}

