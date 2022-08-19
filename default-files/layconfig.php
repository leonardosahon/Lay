<?php
declare(strict_types=1);
session_start();
use Lay\core\LayConfig;
$slash =  DIRECTORY_SEPARATOR;

require_once "Lay" . $slash . "AutoLoader.php";

if(!isset($BOB_D_BUILDER))
    \Lay\core\Exception::throw_exception("BAD REQUEST", "This script cannot be accessed this way, please return home");

///// Project Configuration
$layConfig = LayConfig::instance()
    ->switch([
        "use_prod" => false
    ])
    ->meta([
        "name" => [
            "short" => "Sample Lay Project",
            "full" => "Sample Lay Project | Slogan Goes Here",
        ],
        // PROJECT THEME COLOUR FOR MOBILE AND SUPPORTED PLATFORMS
        "color" => [
            "pry" => "#082a96",
            "sec" => "#0e72e3",
        ],
        "mail" => [
            "EMAIL-1",
            "EMAIL-2",
        ],
        "tel" => [
            "PHONE-NUMBER-1",
            "PHONE-NUMBER-2",
        ]
    ])
    ->others([
        "desc" => "
            This is an awesome project that is about to unfold you just watch and see 😉.
        ",
    ])
    ->init();

// set a custom location for your static assets from Lays' default to yours. Check docs for default locations 
$root = $layConfig->get_res__client("front","root");
$layConfig::set_res__client("front","img",      $root . "assets/images/");
$layConfig::set_res__client("front","css",      $root . "assets/css/");
$layConfig::set_res__client("front","js",       $root . "assets/js/");

// copyright for the footer and your further action 😉
$layConfig::set_site_data("copy","&copy; <a href=\"{$layConfig->get_site_data('base')}\">YOUR-FREELANCE-OR-COMPANY-NAME</a>. " . date("Y") . ". All Rights Reserved");

include_once($layConfig->get_res__server('inc') . "connection.inc");
// add ORM
$layConfig::include_sql(!isset($SQL_EXCLUDE),[
    "prod" => $db_connection,
    "dev" => [
        "host" => "127.0.0.1",
        "user" => "leonard",
        "password" => "root",
        "db" => "",
        "env" => "dev"
    ],
]);