<?php
use Lay\core\LayRequestHandler;

$BOB_D_BUILDER = true;
include_once "layconfig.php";

$req = LayRequestHandler::fetch();

$req->prefix("client")
    ->post("contact")->bind(fn() => \res\server\controller\__front\EndUsers::new()->contact_us())
    ->print_as_json();

$req::end();
