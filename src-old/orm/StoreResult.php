<?php
declare(strict_types=1);
namespace Lay\orm;

use Closure;
use mysqli_result;

class StoreResult extends \Lay\orm\Exception {
    /**
     * @param $exec mysqli_result
     * @param int|null $return_loop int|bool to activate loop or not
     * @param string|null $fetch_as string how result should be returned [assoc|row] default = both
     * @param string $except
     * @param Closure|null $fun a function that should execute at the end of a given row storage
     * @return array|null of result that can be accessed as assoc or row
     */
    public static function store(mysqli_result $exec, ?int $return_loop, ?string $fetch_as="both", string $except = "", Closure $fun = null) : ?array {
        $num_rows = $exec->num_rows;
        $result = null;

        $fetch = match($fetch_as){
            default => MYSQLI_BOTH,
            "assoc" => MYSQLI_ASSOC,
            "row" => MYSQLI_NUM,
        };

        if($return_loop === 1) {
            for ($k = 0; $k < $num_rows; $k++) {
                $result[$k] = mysqli_fetch_array($exec, $fetch);

                if (!empty($except))
                    $result[$k] = self::exempt_column($result[$k], $except);

                if ($fun && $result[$k])
                    $result[$k] = $fun($result[$k], $k);
            }

            return $result;
        }

        $result = mysqli_fetch_array($exec,$fetch);

        if(!empty($except))
            $result = self::exempt_column($result, $except);

        if($fun && $result)
            $result = $fun($result);

        return $result;
    }

    private static function exempt_column(?array $entry, ?string $columns) : array {
        if(!($entry && $columns))
            return [];

        foreach (explode(",",$columns) as $x){
            unset($entry[$x]);
        }

        return $entry;
    }
}