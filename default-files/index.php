<?php
$SQL_EXCLUDE = true;
$BOB_D_BUILDER = true;
include_once "bob_d_builder.php";
$view = \Lay\core\LayConfig::instance()->inject_view();
bob_the_builder(!empty($view) ? $view : ($_GET['f'] ?? "index"));