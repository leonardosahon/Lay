<?php
$SQL_EXCLUDE = true;
$BOB_D_BUILDER = true;
include_once "bob_d_builder.php";
bob_the_builder(\Lay\core\LayConfig::instance()->inject_view() ?: ($_GET['f'] ?? "index"));