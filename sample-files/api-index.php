<?php
use Lay\core\Exception;
use Lay\core\layConfig;

$BOB_D_BUILDER = true;
include_once ".." . DIRECTORY_SEPARATOR . "layconfig.php";

if(isset($_GET['c'])) route_ctrl($_GET['c']);
else
    Exception::throw_exception("This script cannot be accessed this way, please return home","BAD REQUEST");

function route_ctrl(...$controls) : void {
    $layConfig = layConfig::instance();
    foreach ($controls as $ctrl) {
        switch ($ctrl) {
            default:
                Exception::throw_exception("API could not find the requested resource, ensure the parameters are correct and the resource exists. 
                <div style='color: green'>Param: <b style='color: yellow'>$ctrl</b></div>","API FAILURE");
            break;
            case "enu": $layConfig->inc_file("end_users","ctrl_front"); break;
        }
    }
}
