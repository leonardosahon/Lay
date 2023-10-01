<?php
const CONNECT_DB_BY_DEFAULT = false;
const SAFE_TO_INIT_LAY = true;

include_once "layconfig.php";

\Lay\core\LayConfig::new()->add_domain(
    id: "default",
    patterns: ["*"],
    handler: new \res\server\view\DefaultViews()
);
