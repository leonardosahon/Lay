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
        "color" => [
            "pry" => "#082a96",
            "sec" => "#0e72e3",
        ],
        "mail" => [
            "info@osaitech.dev",
            "support@osaitech.dev",
        ],
        "tel" => [
            "07061229417",
            "09055927390",
            "(+234) 706 122 9417",
            "(+234) 905 592 7390",
        ]
    ])
    ->others([
        "desc" => "
            A software company that provides enterprise-level custom software, tailored to meet the specifications of our esteemed clients and businesses.
        ",
    ])
    ->init();

// redirect resources of the back template from Lay's default to the actual template's structure
$root = $layConfig->get_res__client("front","root");
$layConfig::set_res__client("front","img",      $root . "assets/images/");
$layConfig::set_res__client("front","css",      $root . "assets/css/");
$layConfig::set_res__client("front","js",       $root . "assets/js/");

// add theme colour for texts
$layConfig::set_site_data("copy","&copy; <a href=\"{$layConfig->get_site_data('base')}\">Osai Technologies</a>. " . date("Y") . ". All Rights Reserved");

// add page constant tags
$layConfig::set_site_data("others","page_constants", <<<CONST
    <input type="hidden" id="api-endpoint" value="{$layConfig->get_res__client("api")}">
    <input type="hidden" id="uploads-endpoint" value="{$layConfig->get_res__client("upload")}">
    <input type="hidden" id="custom-img-endpoint" value="{$layConfig->get_res__client("custom","img")}">
    <input type="hidden" id="back-img-endpoint" value="{$layConfig->get_res__client("back","img")}">
    <input type="hidden" id="front-img-endpoint" value="{$layConfig->get_res__client("front","img")}">
CONST);

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