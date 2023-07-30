<?php
declare(strict_types=1);
use Lay\core\LayConfig;

$slash =  DIRECTORY_SEPARATOR;
require_once "Lay" . $slash . "AutoLoader.php";

LayConfig::session_start([
    "http_only" => true,
    "only_cookies" => true,
    "secure" => true,
]);

LayConfig::set_cors(
    [],
    false,
    function (){
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Headers: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }
);

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

$layConfig::set_orm(!isset($SQL_EXCLUDE));