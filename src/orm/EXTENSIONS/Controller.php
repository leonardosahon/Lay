<?php
declare(strict_types=1);
namespace Lay\orm\EXTENSIONS;

use Lay\core\sockets\IsSingleton;
use Lay\orm\SQL;

trait Controller{
    use IsSingleton;
    use Clean;
    use SelectorOOP;
}
