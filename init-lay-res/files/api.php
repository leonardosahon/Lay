<?php
const CONNECT_DB_BY_DEFAULT = true;
const SAFE_TO_INIT_LAY = true;

require_once __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "layconfig.php";

$req = \Lay\core\api\ApiEngine::new();

$req->prefix("client")
    ->post("contact")->bind(fn() => \res\server\controller\__front\EndUsers::new()->contact_us())
    ->print_as_json();

$req::end();
