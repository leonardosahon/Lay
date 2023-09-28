<?php
declare(strict_types=1);
namespace Lay\core;

final class LayConfig{
    use \Lay\core\sockets\IsSingleton;
    use \Lay\core\sockets\Init;
    use \Lay\core\sockets\Config;
    use \Lay\core\sockets\Resources;
    use \Lay\core\sockets\Includes;
    use \Lay\core\sockets\Domain;
}
