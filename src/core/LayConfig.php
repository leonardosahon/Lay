<?php
declare(strict_types=1);
namespace Lay\core;

final class LayConfig{
    use \Lay\core\traits\IsSingleton;
    use \Lay\core\traits\Init;
    use \Lay\core\traits\Config;
    use \Lay\core\traits\Resources;
    use \Lay\core\traits\Includes;

    public static function mk_tmp_dir () : string {
        $dir = self::res_server()->temp;

        if(!is_dir($dir)) {
            umask(0);
            mkdir($dir, 0755, true);
        }

        return $dir;
    }
}