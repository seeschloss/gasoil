<?php // vim: ft=php:et:sw=2:sts=2:ts=2

$pleins = match ($voiture) {
  null, "" => Plein::select([], 'date ASC'),
  default => Plein::select(['voiture' => $voiture], 'date ASC'),
};

$tables = [];

$pleins_per_car = [];
foreach ($pleins as $plein) {
  if (!isset($pleins_per_car[$plein->voiture])) {
    $pleins_per_car[$plein->voiture] = [];
  }

  $pleins_per_car[$plein->voiture][$plein->date] = $plein;
}

$previous_plein = null;
foreach ($pleins as $plein) {
  if (!isset($tables[$plein->voiture])) {
    $tables[$plein->voiture] = new Html_Table();
    $tables[$plein->voiture]->title = $plein->voiture;
    $tables[$plein->voiture]->filters = Plein::filters();
    $tables[$plein->voiture]->header = Plein::grid_row_header();
    $tables[$plein->voiture]->rows = [];
  }

  $previous_plein_same_car = null;
  if ($previous_plein and $previous_plein->voiture == $plein->voiture) {
    $previous_plein_same_car = $previous_plein;
  } else {
    $previous_pleins_same_car = array_filter($pleins_per_car[$plein->voiture], fn($p) => $p->date < $plein->date);
    $previous_plein_same_car = array_pop($previous_pleins_same_car);
  }


  if ($previous_plein_same_car and ($previous_plein_same_car->plein or $plein->plein)) {
    $tables[$plein->voiture]->rows[$plein->date."-interval"] = [
      'cells' => $plein->grid_row_interval($previous_plein_same_car),
      'attributes' => [
        'class' => 'interval',
      ],
    ];
  }

  $tables[$plein->voiture]->rows[$plein->date] = [
    'cells' => $plein->grid_row(),
    'attributes' => [
      'class' => 'plein',
    ],
  ];


  /*
  $tables[$plein->voiture]->rows[] = [
    'cells' => $plein->grid_row_single($previous_plein_same_car),
    'attributes' => [
      'class' => 'plein',
    ],
  ];
   */
  $previous_plein = $plein;
};

$tables = array_reverse($tables);

foreach ($tables as $voiture => $table) {
  $table->rows = array_reverse($table->rows);
  echo <<<HTML
<div id="pleins">{$table->html()}</div>
HTML;
}

?>

