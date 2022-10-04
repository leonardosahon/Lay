<?php
$SQL_EXCLUDE = true; // Exclude DB Connection be default
$BOB_D_BUILDER = true; // Used to ensure the `layconfig.php` and `bob_d_builder.php` files are accessed correctly

include_once "layconfig.php";
include_once "bob_d_builder.php";

$layConfig->add_domain([], null, fn($view) => bob_d_builder($view));
