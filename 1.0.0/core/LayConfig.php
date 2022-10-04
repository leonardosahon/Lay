<?php
declare(strict_types=1);
namespace Lay\core;

final class LayConfig{
    private static self $instance;
    private static bool $INITIALIZED = false;

    private function __construct(){}
    private function __clone(){}
    private static function is_init() : void {
        if(!self::$INITIALIZED)
            Exception::throw_exception(
                "Lay has not been initialized properly, ensure to use the `->init()` function after getting `LayConfig::instance()`.<br><br>
                    <u>Example</u>
                    LayConfig::instance()<br>->project('CURRENT_PROJECT_FOLDER_NAME')<br>->init();<br>
                    // If nothing is passed to the `->project()` function, Lay will assume the project resides at the root folder 
                    ",
                "Wrong-Init-Procedure");
    }

    public static function instance() : self{
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    use \Lay\core\sockets\Config;
    use \Lay\core\sockets\Resources;
    use \Lay\core\sockets\Includes;
    use \Lay\core\sockets\View;
}