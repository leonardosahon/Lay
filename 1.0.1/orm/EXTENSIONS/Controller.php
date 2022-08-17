<?php
declare(strict_types=1);
namespace Lay\orm\EXTENSIONS;

use Lay\orm\SQL;

trait Controller{
    use SelectorOOP;
    use SelectorProcedure;
    use Clean;
    use MultiQuery;
    use OneLiner;
    use ResultGen;

    protected static function core() : SQL {
        return SQL::instance();
    }
}