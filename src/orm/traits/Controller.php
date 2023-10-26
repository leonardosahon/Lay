<?php
declare(strict_types=1);
namespace Lay\orm\traits;

use Lay\core\traits\IsSingleton;
use Lay\orm\SQL;

trait Controller{
    use IsSingleton;
    use Clean;
    use SelectorOOP;
}
