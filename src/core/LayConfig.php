<?php
declare(strict_types=1);
namespace Lay\core;

final class LayConfig{
    use \Lay\core\sockets\IsSingleton;
    use \Lay\core\sockets\Init;
    use \Lay\core\sockets\Config;
    use \Lay\core\sockets\Resources;
    use \Lay\core\sockets\Includes;

    public static function mk_tmp_dir () : string {
        $dir = self::res_server()->temp;

        if(!is_dir($dir)) {
            umask(0);
            mkdir($dir, 0755, true);
        }

        return $dir;
    }
}