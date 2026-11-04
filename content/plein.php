<?php // vim: ft=html:et:sw=2:sts=2:ts=2
if (isset($_POST['action']) and isset($_POST['plein'])) {
  switch ($_POST['action']) {
    case 'insert':
      $plein = new Plein();
      $plein->from_form($_POST['plein']);
      if ($plein->insert()) {
        header("Location: {$GLOBALS['config']['base_path']}");
      }
      break;
    case 'delete':
      $plein = new Plein();
      if ($plein->load(['id' => $_POST['plein']['id']])) {
        $plein->delete();
        header("Location: {$GLOBALS['config']['base_path']}");
      }
      break;
  }
}
?>

<div id="plein">
  <?= $plein->form() ?>
</div>
