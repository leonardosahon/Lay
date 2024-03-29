<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . "Lay" . DIRECTORY_SEPARATOR . "AutoLoader.php";

use Lay\core\LayConfig;

LayConfig::validate_lay();

LayConfig::session_start([
    "http_only" => true,
    "only_cookies" => true,
    "secure" => true,
    "samesite" => 'None',
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

$site_name = "Sample Lay Project";

///// Project Configuration
$layConfig = LayConfig::new();

$GLOBALS['layConfig'] = $layConfig;

$layConfig
    ->dont_use_prod_folder()
    ->init_name($site_name, "$site_name | Slogan Goes Here")
    ->init_color("#082a96", "#0e72e3")
    ->init_mail("EMAIL-1", "EMAIL-2")
    ->init_tel("TEL-1", "TEL-2")
    ->init_others([
        "desc" => "
            This is an awesome project that is about to unfold you just watch and see 😉.
        ",
    ])
    ->init_copyright("&copy; <a href=\"{$layConfig->get_site_data('base')}\">$site_name</a>. " . date("Y") . ". All Rights Reserved")
    ->init_orm(defined('CONNECT_DB_BY_DEFAULT') && CONNECT_DB_BY_DEFAULT);

// Set a custom location for your static assets from Lays' default to yours.
// For default locations use:
// var_dump($layConfig->get_res__client('front'));
$layConfig::set_res__client("front", "img", "@front/assets/images/");
$layConfig::set_res__client("front", "css", "@front/assets/css/");
$layConfig::set_res__client("front", "js", "@front/assets/js/");
