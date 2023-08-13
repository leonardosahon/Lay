<?php
declare(strict_types=1);
namespace Lay\orm\EXTENSIONS;

trait LegacyQueries
{

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
        return $this->query($q, ["query_type" => "insert"], $debug,$option);
    }
    /**
     * SELECT
     * @param array|string $columns Columns to select
     * WHEN ARRAY === [
     * "table" => {from_table}, "cols" => {selected_columns},
     * "clause" => "query clause or condition",
     * "join" => [
     * [
     * "table" => {join_table},
     * {optional} "type" => "left|right|inner",
     * "on" => [join_table_column,{from_table_column || previous_sibling_table_column}]
     * ],
     * ]
     * ]
     * @param mixed ...$option args = TABLE,CLAUSE,FETCH_AS,LOOP,DEBUG;
     * // NOTE: if the query will use clause, it must be first arg, else every other arg can be put in any order
     * @return array|null
     */
    final public function query_select(array|string $columns, ...$option) : ?array {
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
                $join = match ($join) {
                    "left", "inner", "right" => strtoupper($join),
                    default => "",
                };
                $on = $col_opt['on'];

                $join_table .= "$join JOIN {$col_opt['table']} ON $on[0] = $on[1] ";
            }
            $table = $columns['table'] ?? $table;
            $final_columns = $columns['cols'];
            $clause = $columns['clause'] ?? $clause;
            $clause = $join_table . $clause;
        }

        $option = array_merge($option,["result","query_type" => "select"]);
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
        return $this->query("SELECT COUNT($column) FROM $table $clause", ["query_type" => "count"],$debug,$option);
    }
    /**
     * UPDATE
     * @param string $table table to update
     * @param array|string $update_values values to update, in multi core the array shape takes the form:
     * [
     * "values" => "{`col`=val},{`col`=val}...",
     * "match" => [[
     * "column" => {Column to update when case returns true}"
     * "switch" => "{The column that determines what will be updated in the column above, it is tested based on the cases below}",
     * "case" =>[
     * {WHEN switch value == value 1} => {value to update},
     * {WHEN switch value == value 2} => {value to update},
     * ...
     * ]
     * ],...
     * ]
     * @param string|null $clause or criteria on which update is carried out, null means update the entire table
     * @param mixed $misc other options required to tweak function, pass "multi" for multiple updates in one query
     * @return bool returns true on execution
     *@example {in_array("multi",$misc,true)} SQL_CORE::query_update($table,[
     * "values" => "`column`='value',`column`='value',`column`='value',...",
     * "match" => [
     * [
     * "column" => "another_column",
     * "switch" => "id_or_determinant_column",
     * "case" => [
     * "when_case_1_is_true" => "another_column_value", {then}
     * "when_case_2_is_true" => "another_column_value", {then}
     * "when_case_3_is_true" => "another_column_value", {then}
     * ]
     * ],...
     * ]
     * ],$clause,$misc)
     */
    final public function query_update(string $table, array|string $update_values, ?string $clause = null, ...$misc) : bool {
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

        return $this->query("UPDATE $table SET $update_values $clause", ["query_type" => "update"],$debug,$misc);
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
        return $this->query("DELETE FROM $table WHERE $where", ["query_type" => "delete"], $debug);
    }
}