<?php
include_once "config_example.php";
\Lay\core\LayConfig::instance()->view([
    "core" => [
        "strict" => false,
    ],
    "page" => [
        "title" => "Lay Framework",
        "type" => "front",
    ],
    "view" => [
        "body" => "index",
    ],
]);