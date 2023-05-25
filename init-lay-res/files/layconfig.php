<?php
declare(strict_types=1);
session_start();
use Lay\core\LayConfig;
$slash =  DIRECTORY_SEPARATOR;

require_once "Lay" . $slash . "AutoLoader.php";

if(!isset($BOB_D_BUILDER))
    \Lay\core\Exception::throw_exception("BAD REQUEST", "This script cannot be accessed this way, please return home");

$site_name = "Sample Lay Project";

///// Project Configuration
$layConfig = LayConfig::instance()
    ->switch([
        "use_prod" => false
    ])
    ->meta([
        "name" => [
            "short" => $site_name,
            "full" => "$site_name | Slogan Goes Here",
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
    ]);

// set a custom location for your static assets from Lays' default to yours. Check docs for default locations 
$root = $layConfig->get_res__client("front","root");
$layConfig::set_res__client("front","img",      $root . "assets/images/");
$layConfig::set_res__client("front","css",      $root . "assets/css/");
$layConfig::set_res__client("front","js",       $root . "assets/js/");

// copyright for the footer and your further action 😉
$layConfig::set_site_data("copy","&copy; <a href=\"{$layConfig->get_site_data('base')}\">$site_name</a>. " . date("Y") . ". All Rights Reserved");

$includes_dir = $layConfig->get_res__server('inc');

if($layConfig::get_env() == "DEV")
    include_once($includes_dir . "dev_connection.inc");
else
    include_once($includes_dir . "prod_connection.inc");

// add ORM
$layConfig::include_sql(!isset($SQL_EXCLUDE), $db_connection);