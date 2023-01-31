<?php
declare(strict_types=1);
namespace Lay\core;

final class LayConfig{
    private static self $instance;

    private function __construct(){}
    private function __clone(){}

    public static function instance() : self{
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    use \Lay\core\sockets\Init;
    use \Lay\core\sockets\Config;
    use \Lay\core\sockets\Resources;
    use \Lay\core\sockets\Includes;
    use \Lay\core\sockets\View;
}