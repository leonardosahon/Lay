<?php
$SQL_EXCLUDE = true; // Exclude DB Connection by default
$BOB_D_BUILDER = true; // Used to ensure the `layconfig.php` and `builder_default.php` files are accessed correctly

include_once "layconfig.php";
$layConfig = \Lay\core\LayConfig::new();

$layConfig->add_domain(
    id: "default",
    patterns: ["*"],
    handler: function($route, $route_as_array, $pattern, $domain_type) use ($SQL_EXCLUDE, $BOB_D_BUILDER) {
        include_once "builder_default.php";
        builder_default($route, $route_as_array, $pattern, $domain_type);
    }
);
