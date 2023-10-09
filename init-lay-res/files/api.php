<?php
const CONNECT_DB_BY_DEFAULT = true;
const SAFE_TO_INIT_LAY = true;

include_once "layconfig.php";

$req = \Lay\core\LayRequestHandler::fetch();

$req->prefix("client")
    ->post("contact")->bind(fn() => \res\server\controller\__front\EndUsers::new()->contact_us())
    ->print_as_json();

$req::end();
