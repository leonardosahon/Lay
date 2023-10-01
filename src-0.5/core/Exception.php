<?php
declare(strict_types=1);
namespace Lay\core;

use Lay\orm\SQL;

abstract class Exception {
    /**
     * @throws \Exception
     */
    public static function throw_exception(string $message, string $title = "Generic", bool $kill = true) : void {
        SQL::instance()->use_exception("LayConfig::ERR::$title",$message,$kill);
    }
}