<?php
$SQL_EXCLUDE = true; // Exclude DB Connection by default
$BOB_D_BUILDER = true; // Used to ensure the `layconfig.php` and `bob_d_builder.php` files are accessed correctly

include_once "layconfig.php";
include_once "builder_default.php";

$layConfig->add_domain([], default_fn: fn($view) => bob_d_builder($view));
