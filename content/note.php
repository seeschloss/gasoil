<?php // vim: ft=html:et:sw=2:sts=2:ts=2
if (isset($_POST['action']) and isset($_POST['note'])) {
  switch ($_POST['action']) {
    case 'insert':
    case 'update':
      $note = new Note();
      $note->from_form($_POST['note']);
      if ($note->save()) {
        header("Location: {$GLOBALS['config']['base_path']}");
      }
      break;
    case 'delete':
      $note = new Note();
      if ($note->load(['id' => $_POST['note']['id']])) {
        $note->delete();
        header("Location: {$GLOBALS['config']['base_path']}");
      }
      break;
  }
}
?>

<div id="note">
  <?= $note->form() ?>
</div>
