<?php
declare(strict_types=1);
namespace Lay\orm\EXTENSIONS;

use Lay\orm\SQL;

trait Controller{
    use IsSingleton;
    use Clean;
    use OneLiner;
    use SelectorOOP;

    /**
     * @deprecated
     */
    use SelectorProcedure;
    /**
     * @deprecated
     */
    use LegacyQueries;

    #!optional
    use ArraySearch;

    protected static function core() : SQL {
        return SQL::instance();
    }
}