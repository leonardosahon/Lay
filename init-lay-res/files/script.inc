<?php
global $view;

\Lay\core\view\ViewAsset::new()->script("@front/js/another.js");

echo $view->script;
