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

    private static self $instance;
    private function __construct(){}
    private function __clone(){}
    public string $query;

    public static function instance() : self {
        if(!isset(self::$instance))
            self::$instance = new self();
        return self::$instance;
    }
    /**
     * @param $connection mysqli|array|null The link to a mysqli connection or an array of [host, user, password, db]
     * When nothing is passed, the class assumes dev isn't doing any db operation
     */
    public static function init($connection = null) : self {
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
     * Enhanced array search, this will search for values even in multiple dimensions of arrays
     * @param string $needle
     * @param array $haystack
     * @param bool $strict choose between == or === comparison operator
     * @param int $total_dimension ***Do not modify this option, it is readonly to the developer***
     * @return string[]
     */
    final public function array_search(string $needle, array $haystack, bool $strict = false, int $total_dimension = 0) : array {
        $result = [
            "value" => "LAY_NULL",
        ];

        foreach ($haystack as $i => $d){
            if(is_array($d)){
                ++$total_dimension;
                $result['index_d' . $total_dimension] = $i;
                $search = $this->array_search($needle, $d, $strict, $total_dimension);

                if($search['value'] !== "LAY_NULL") {
                    $result = array_merge($result,$search);
                    break;
                }
                --$total_dimension;
                continue;
            }

            if(($strict === false && $needle == $d)){
                $total_dimension++;
                $result['index_d' . $total_dimension] = $i;
                $result['value'] = $d;
                break;
            }

            if(($strict === true && $needle === $d)){
                $total_dimension++;
                $result['index_d' . $total_dimension] = $i;
                $result['value'] = $d;
                break;
            }
        }

        return $result;
    }

    /**
     * INSERT
     * @param string $table table for insertion
     * @param mixed ...$option args = {$col || $col=$col_val} must be first item; other args {$col_val}, {DEBUG};
     * There are several modes in the function depending on your construction: 'combo' [default] || 'multi' || 'multi2' || 'classic';
     * [combo] {$col=$col_val,$col2='$col_val2',...}
     * [multi] {($col,$col2,...)}, {VALUES ($col_val), ($col_val2),...}
     * [multi2] {$col,$col2}, {($col_val),($col_val2)}
     * construct within the rules of SQL and max_allowed_packet;
     * Above {} represents an argument; i.e query_insert({},{},{})
     * {int} DEBUG = 1 | 0; // 1 to show the query the way SQL is receiving it
     * @example query_insert("$table","`$col`='$col_val',`$col2`='$col_val2',`$col3`='$col_val3'")
     * @example query_insert("$table","`$col`,`$col2`,`$col3`","'$col_val',$col_val2',$col_val3',")
     * @return bool|null
     */
    final public function query_insert(string $table, ...$option) : ?bool {
        $option = $this->array_flatten($option);
        $debug = 0;
        $insert_clause = $option[0];
        $values = null;
        $first_string = trim(substr($option[0], 0, 1));
        $mode = $first_string === "(" ? "multi" : "combo";

        if(in_array(1,$option,true))
            $debug = 1;

        if(count($option) > 1 && !is_int($option[1]) && !is_null($option[1])){
            $values = $option[1];
            $first_string = !is_int($values) ? trim(substr($values,0,1)) : "";
            if($first_string === "(")
                $mode = "multi2";
            elseif(!is_int($values))
                $mode = ($first_string === "v" || $first_string === "V") ? "multi" : "classic";
        }

        if($mode == "combo")
            $q = "INSERT INTO $table SET $insert_clause";
        elseif($mode == "multi")
            $q = "INSERT INTO $table $insert_clause";
        else{
            $insert_clause = implode(",", array_map(fn($v) => "`".trim($v)."`", explode(",", str_replace("`","", $insert_clause))));
            $values = rtrim($values,",");
            $values = $mode == "multi2" ? "$values" : "($values)";
            $q = "INSERT INTO $table ($insert_clause) VALUES $values";
        }
        return $this->query($q, "insert", $debug,$option);
    }
    /**
     * SELECT
     * @param array|string $columns Columns to select
     * WHEN ARRAY === [
    "table" => {from_table}, "cols" => {selected_columns},
    "clause" => "query clause or condition",
    "join" => [
    [
    "table" => {join_table},
    {optional} "type" => "left|right|inner",
    "on" => [join_table_column,{from_table_column || previous_sibling_table_column}]
    ],
    ]
     * ]
     * @param mixed ...$option args = TABLE,CLAUSE,FETCH_AS,LOOP,DEBUG;
     * // NOTE: if the query will use clause, it must be first arg, else every other arg can be put in any order
     * @return array|null
     */
    final public function query_select($columns, ...$option) : ?array {
        $option = $this->array_flatten($option);
        $table = $option[0] ?? null;
        $debug = 0;
        $option[1] = $option[1] ?? null;
        $clause = null;
        $final_columns = $columns;

        if(in_array(1,$option,true))
            $debug = 1;

        if($option[1] != "assoc" && $option[1] != "row" && $option[1] != "loop")
            $clause = $option[1];

        if(is_array($columns)) {
            $join_table = "";

            foreach ($columns['join'] as $col_opt) {
                $join = strtolower($col_opt['type'] ?? $col_opt['join'] ?? "");
                switch ($join){
                    case "left":case "inner":case "right": $join = strtoupper($join); break;
                    default: $join =  ""; break;
                }
                $on = $col_opt['on'];

                $join_table .= "$join JOIN {$col_opt['table']} ON $on[0] = $on[1] ";
            }
            $table = $columns['table'] ?? $table;
            $final_columns = $columns['cols'];
            $clause = $columns['clause'] ?? $clause;
            $clause = $join_table . $clause;
        }

        $option = array_merge($option,["result","select"]);
        if($table) $table = "FROM $table";
        return $this->query("SELECT $final_columns $table $clause",$option,$debug);
    }
    /**
     * COUNT
     * @param string $column single column to count
     * @param mixed ...$option
     * @return int
     */
    final public function query_count(string $column, ...$option) : int {
        $option = $this->array_flatten($option);
        $debug = 0;
        $clause = null;
        $table = $option[0];
        $option[1] = $option[1] ?? null;
        if(in_array(1,$option,true))
            $debug = 1;
        if(!is_int($option[1]))
            $clause = $option[1];
        return $this->query("SELECT COUNT($column) FROM $table $clause", "count",$debug,$option);
    }
    /**
     * UPDATE
     * @param string $table table to update
     * @param string|array $update_values values to update, in multi core the array shape takes the form:
     * [
    "values" => "{`col`=val},{`col`=val}...",
    "match" => [[
    "column" => {Column to update when case returns true}"
    "switch" => "{The column that determines what will be updated in the column above, it is tested based on the cases below}",
    "case" =>[
    {WHEN switch value == value 1} => {value to update},
    {WHEN switch value == value 2} => {value to update},
    ...
    ]
    ],...
    ]
     * @example {in_array("multi",$misc,true)} SQL_CORE::query_update($table,[
    "values" => "`column`='value',`column`='value',`column`='value',...",
    "match" => [
    [
    "column" => "another_column",
    "switch" => "id_or_determinant_column",
    "case" => [
    "when_case_1_is_true" => "another_column_value", {then}
    "when_case_2_is_true" => "another_column_value", {then}
    "when_case_3_is_true" => "another_column_value", {then}
    ]
    ],...
    ]
    ],$clause,$misc)
     * @param string|null $clause or criteria on which update is carried out, null means update the entire table
     * @param mixed $misc other options required to tweak function, pass "multi" for multiple updates in one query
     * @return bool returns true on execution
     */
    final public function query_update(string $table, $update_values, ?string $clause = null, ...$misc) : bool {
        $debug = 0;
        if(in_array(1,$misc,true))
            $debug = 1;

        if(is_array($update_values)) {
            $case_value = "";
            $clause = !$clause ? "" : $clause . " AND ";

            foreach ($update_values['match'] as $v) {
                $switch = $v['switch'];
                $case = "";
                $case_list = "";
                $column = $v['column'];

                foreach ($v['case'] as $j => $c){
                    $case .= "WHEN '$j' THEN $c ";
                    $case_list .= "'$j',";
                }

                $case_list = "(" . rtrim($case_list, ",") . ")";
                $case_value .= "`$column` = CASE `$switch` $case END,";

                $clause .= " `$switch` IN $case_list AND";
            }

            $update_values = @$update_values['values'] ? $update_values['values'] . "," : "";
            $update_values .= rtrim($case_value, ",");
            $clause = rtrim($clause," AND");
        }

        return $this->query("UPDATE $table SET $update_values $clause", "update",$debug,$misc);
    }
    /**
     * DELETE
     * @param string $table table to insert into
     * @param string $where criteria to follow to ensure delete
     * @param int $debug to display query before execution, for troubleshooting
     * @return bool returns true on execution
     **/
    final public function query_delete(string $table, string $where, int $debug=0) : bool {
        $where = strtolower(substr(trim($where),0,5)) == "where" ? trim(substr(trim($where),5)) : $where;
        return $this->query("DELETE FROM $table WHERE $where", "delete", $debug);
    }
    /**
     * Query Engine
     * @param string $query
     * @param mixed ...$option Tweak function;
     * args = "assoc||row", "loop","run||result", "!||!null||not_null", "query_type", {int} debug;
     * if you want to access the mysqli_query directly, pass "exec"
     * @return int|bool|array|null|mysqli_result
     */
    final public function query(string $query, ...$option) {
        $this->query = "<div>$query</div>";
        $debug = 0;
        $option = $this->array_flatten($option);
        $return = "result";
        ///////////// OPTIONS ///////////
        ///RETURN TYPE
        if(in_array(1,$option,true)) $debug = 1;
        if(in_array("exec",$option,true)) $return = "exec";
        ///QUERY TYPE
        if(in_array("insert" ?? "INSERT",$option,true)) $query_type = "INSERT";
        elseif(in_array("select" ?? "SELECT",$option,true)) $query_type = "SELECT";
        elseif(in_array("count" ?? "COUNT",$option,true)) $query_type = "COUNT";
        elseif(in_array("update" ?? "UPDATE",$option,true)) $query_type = "UPDATE";
        elseif(in_array("delete" ?? "DELETE",$option,true)) $query_type = "DELETE";
        elseif(in_array("last_insert",$option,true)) $query_type = "LAST_INSERTED";
        elseif(!(@$option['type'])){
            $qr = explode(" ", trim($query),2);
            $query_type = strtoupper(substr($qr[1],0,5));
            $query_type = $query_type == "COUNT" ? $query_type : strtoupper($qr[0]);
        }
        else $query_type = $option['type'] ?? $option['custom'];
        ///LOOP AND FETCH AS
        if(in_array("loop" ?? "LOOP",$option,true)) $loop = 1;
        if(in_array("row" ?? "ROW",$option,true)) $as = "row";
        if(in_array("assoc" ?? "ROW",$option,true)) $as = "assoc";

        // Debug
        $option['debug'][0] = $query;  $option['debug'][1] = $query_type;
        if($debug) $this->show_exception(-9, $option['debug']);

        $exec = false;

        try{
            $exec = mysqli_query(self::$link,$query);
        }catch (\Exception $e){}

        if($exec === false)
            $this->show_exception(-10,$option['debug']);

        // Sort out result

        if ($query_type == "COUNT") return (int) mysqli_fetch_row($exec)[0];
        if(($query_type == "SELECT" || $query_type == "LAST_INSERTED") && $return == "result") {
            $loop = $option['loop'] ?? $loop ?? null;
            $except = $option['except'] ?? "";
            $fun = $option['fun'] ?? null;
            $exec = $this->store_result($exec, $loop, $as ?? null, $except, $fun);
            if(in_array("!" ?? "!null" ?? "not_null",$option,true))
                $exec = $exec ?? [];

            return $exec;
        }
        if(!(in_array("weak",$option,true) || in_array("~",$option,true)) && mysqli_affected_rows(self::$link) == 0) return false;
        return $exec;
    }

    /**
     * @param $exec mysqli_result
     * @param int|null $return_loop int|bool to activate loop or not
     * @param string|null $fetch_as string how result should be returned [assoc|row] default = both
     * @param string $except
     * @param Closure|null $fun a function that should execute at the end of a given row storage
     * @return array|null of result that can be accessed as assoc or row
     */
    private function store_result(mysqli_result $exec, ?int $return_loop, ?string $fetch_as="both", string $except = "", Closure $fun = null) : ?array {
        $num_rows = $exec->num_rows; $result = null;
        if($fetch_as == "assoc") $fetch = MYSQLI_ASSOC;  elseif($fetch_as == "row") $fetch = MYSQLI_NUM;
        else $fetch = MYSQLI_BOTH;
        if($return_loop == 1) { for($k = 0; $k < $num_rows; $k++) {
            $result[$k] = mysqli_fetch_array($exec,$fetch);
            if(!empty($except))
                $result[$k] = $this->exempt_column($result[$k], $except);
            if($fun && $result[$k])
                $result[$k] = $fun($result[$k], $k);
        }}
        else {
            $result = mysqli_fetch_array($exec,$fetch);
            if(!empty($except))
                $result = $this->exempt_column($result, $except);
            if($fun && $result)
                $result = $fun($result);
        }
        return $result;
    }
    private function exempt_column(?array $entry, ?string $columns) : array {
        if(!($entry && $columns)) return [];
        foreach (explode(",",$columns) as $x){
            unset($entry[$x]);
        }
        return $entry;
    }
}