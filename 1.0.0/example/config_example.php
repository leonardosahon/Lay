<?php
# This a sample script of how the LayConfig.php file is access. This script can be used in a project
# It script is responsible for relaying the project-custom parameters to the LayConfig file of Lay Framework

declare(strict_types=1);
namespace Lay\example;
session_start();
use Lay\core\LayConfig;

require_once "../AutoLoader.php";

///// Project Configuration
$layConfig = LayConfig::instance()->init();

$layConfig::set_res__server("inc", __DIR__ . DIRECTORY_SEPARATOR . "includes" . DIRECTORY_SEPARATOR);
$layConfig::set_res__server("view", __DIR__ . DIRECTORY_SEPARATOR . "view" . DIRECTORY_SEPARATOR);