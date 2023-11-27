<?php
declare(strict_types=1);
namespace Lay\orm;

use JetBrains\PhpStorm\ExpectedValues;
use mysqli;
use Closure;
use mysqli_result;

/**
 * Simple Query Language
 **/
class SQL extends \Lay\orm\Exception {
    use Config;
    use traits\Controller;

    public array $query_info;

    /**
     * @param $connection mysqli|array|null The link to a mysqli connection or an array of [host, user, password, db]
     * When nothing is passed, the class assumes dev isn't doing any db operation
     */
    public static function init(mysqli|array|null $connection = null) : self {
        self::_init($connection);
        return self::instance();
    }

    /**
     * Turns any number of dimensions of an array to a single dimension array. 
     * The latest values will replace arrays with the same keys  
     * @param array $array 
     * @return array
     */
    final public function array_flatten(array $array) : array {
        $arr = $array;
        if(count(array_filter($array,"is_array")) > 0) {
            $arr = [];
            foreach ($array as $i => $v) {
                if (is_array($v)) {
                    array_walk($v, function ($entry,$key) use (&$arr,&$v) {
                        if (is_array($entry))
                            $arr = array_merge($arr, $entry);
                        elseif (!is_int($key))
                            $arr[$key] = $entry;
                        else
                            $arr[] = $entry;
                    });
                }
                else
                    $arr[$i] = $v;
            }
        }
        return $arr;
    }

    public function switch_db(string $name) : bool {
        $name = mysqli_real_escape_string(self::$link, $name);
        return mysqli_select_db(self::$link, $name);
    }

    /**
     * Query Engine
     * @param string $query
     * @param array $option Adjust the function to fit your use case;
     * @return int|bool|array|null|mysqli_result
     * @throws \Exception
     */
    final public function query(string $query, array $option = []) : int|bool|array|null|mysqli_result {
        if(!isset(self::$link))
            $this->show_exception(0);

        $option = $this->array_flatten($option);
        $debug = $option['debug'] ?? 0;
        $catch_error = $option['catch'] ?? 0;
        $return_as = $option['return_as'] ?? "result"; // exec|result
        $can_be_null = $option['can_be_null'] ?? true;
        $can_be_false = $option['can_be_false'] ?? true;
        $query_type = strtoupper($option['query_type'] ?? "");

        if(empty($query_type)){
            $qr = explode(" ", trim($query),2);
            $query_type = strtoupper(substr($qr[1],0,5));
            $query_type = $query_type == "COUNT" ? $query_type : strtoupper($qr[0]);
        }

        // prepare to show a query for review if the correct parameter is passed
        $option['debug'] = [];
        $option['debug'][0] = $query;
        $option['debug'][1] = $query_type;

        if($debug)
            $this->show_exception(-9, $option['debug']);

        // execute query
        $exec = false;
        $has_error = false;
        try{
            $exec = mysqli_query(self::$link,$query);
        } catch (\Exception){
            $has_error = true;
            if($exec === false && $catch_error === 0)
                $this->show_exception(-10,$option['debug']);
        }

        // init query info structure
        $this->query_info = [
            "status" => QueryStatus::success,
            "has_data" => true,
            "data" => $exec,
            "has_error" => $has_error
        ];

        if ($query_type == "COUNT")
            return $this->query_info['data'] = (int)mysqli_fetch_row($exec)[0];

        // prevent select queries from returning bool
        if(in_array($query_type, ["SELECT","LAST_INSERT"]))
            $can_be_false = false;

        // Sort out result
        if (mysqli_affected_rows(self::$link) == 0) {
            $this->query_info['has_data'] = false;

            if($query_type == "SELECT" || $query_type == "LAST_INSERTED")
                return $this->query_info['data'] = !$can_be_null ? [] : null;

            if($can_be_false)
                $this->query_info['data'] = false;

            return $this->query_info['data'];
        }

        if (!$exec) {
            $this->query_info = [
                "status" => QueryStatus::fail,
                "has_data" => false,
                "has_error" => $has_error,
            ];

            if($can_be_false)
                return $this->query_info['data'] = false;

            return $this->query_info['data'] = !$can_be_null ? [] : null;
        }

        if(($query_type == "SELECT" || $query_type == "LAST_INSERTED") && $return_as == "result") {
            $exec = StoreResult::store(
                $exec,
                $option['loop'] ?? null,
                $option['fetch_as'] ?? null,
                $option['except'] ?? "",
                $option['fun'] ?? null,
                $option['result_dimension'] ?? 2
            );

            if(!$can_be_null)
                $exec = $exec ?? [];

            $this->query_info['data'] = $exec;
        }

        return $exec;
    }
}