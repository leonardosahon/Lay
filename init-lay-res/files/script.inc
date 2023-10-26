<?php
global $view;

\Lay\core\view\ViewAsset::new()->script("@custom/js/another.js");

echo $view->script;
