<?php
const CONNECT_DB_BY_DEFAULT = false;
const SAFE_TO_INIT_LAY = true;

include_once "layconfig.php";

\Lay\core\view\ViewDomain::new()->create(
    id: "default",
    patterns: ["*"],
    builder: new \res\server\view\DefaultViews()
);
