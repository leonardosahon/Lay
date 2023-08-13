<?php
declare(strict_types=1);
namespace Lay\orm;

use mysqli;
use Closure;
use mysqli_result;

/**
 * Simple Query Language
 **/
class SQL extends \Lay\orm\Exception {
    use Config;
    use EXTENSIONS\Controller;

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
            foreach ($array as $v) {
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
                    $arr[] = $v;
            }
        }
        return $arr;
    }

    /**
     * Query Engine
     * @param string $query
     * @param mixed ...$option Tweak function;
     * args = "assoc||row", "loop","run||result", "!||!null||not_null", "query_type", {int} debug;
     * if you want to access the mysqli_query directly, pass "exec"
     * @return int|bool|array|null|mysqli_result
     */
    final public function query(string $query, mixed ...$option) : int|bool|array|null|mysqli_result {
        $option = $this->array_flatten($option);
        $debug = $option['debug'] ?? 0;
        $catch_error = $option['catch'] ?? 0;
        $return = "result";
        $can_be_null = $option['can_be_null'] ?? true;
        $can_be_false = $option['can_be_false'] ?? true;
        $query_type = strtoupper($option['query_type'] ?? "");

        ///RETURN TYPE
        if(in_array("exec",$option,true))
            $return = "exec";

        if(in_array("!" ?? "!null" ?? "not_null",$option,true))
            $can_be_null = false;

        if(in_array("weak",$option,true) || in_array("~",$option,true))
            $can_be_false = false;

        if(empty($query_type)){
            $qr = explode(" ", trim($query),2);
            $query_type = strtoupper(substr($qr[1],0,5));
            $query_type = $query_type == "COUNT" ? $query_type : strtoupper($qr[0]);
        }

        ///LOOP AND FETCH AS
        if(in_array("loop" ?? "LOOP",$option,true)) $loop = 1;
        if(in_array("row" ?? "ROW",$option,true)) $as = "row";
        if(in_array("assoc" ?? "ASSOC",$option,true)) $as = "assoc";

        // prepare to show a query for review if the correct parameter is passed
        $option['debug'] = [];
        $option['debug'][0] = $query;
        $option['debug'][1] = $query_type;

        if($debug)
            $this->show_exception(-9, $option['debug']);

        // execute query
        $exec = false;
        try{
            $exec = mysqli_query(self::$link,$query);
        } catch (\Exception $e){
            if($exec === false && $catch_error === 0)
                $this->show_exception(-10,$option['debug']);
        }

        // init query info structure
        $this->query_info = [
            "status" => SQLEnums::success,
            "has_data" => true,
            "data" => $exec
        ];

        ////// return result of a query

        if ($query_type == "COUNT")
            return $this->query_info['data'] = (int)mysqli_fetch_row($exec)[0];

        // prevent select queries from returning bool
        if(in_array($query_type,["SELECT","LAST_INSERT"]))
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
                "status" => SQLEnums::fail,
                "has_data" => false,
            ];

            if($can_be_false)
                return $this->query_info['data'] = false;

            return $this->query_info['data'] = !$can_be_null ? [] : null;
        }

        if(($query_type == "SELECT" || $query_type == "LAST_INSERTED") && $return == "result") {
            $exec = SQLStoreResult::store(
                $exec,
                $option['loop'] ?? $loop ?? null,
                $as ?? null,
                $option['except'] ?? "",
                $option['fun'] ?? null
            );

            if(!$can_be_null)
                $exec = $exec ?? [];

            $this->query_info['data'] = $exec;
        }

        return $exec;
    }
}