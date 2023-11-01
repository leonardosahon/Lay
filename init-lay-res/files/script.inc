<?php
global $view;

\Lay\core\view\tags\Script::new()->src("@custom/js/another.js");

echo $view->script;
