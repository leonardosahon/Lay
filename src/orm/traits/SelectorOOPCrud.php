<?php

namespace Lay\orm\traits;

use Lay\orm\SQL;

trait SelectorOOPCrud
{
    private mixed $saved_result;

    private function capture_result(array $result_and_opt, string $return_type = 'array') : mixed {
        $opt = $result_and_opt[1];

        $this->saved_result = $result_and_opt[0];

        if(!call_user_func("is_$return_type", $this->saved_result)) {
            $type = gettype($this->saved_result);

            if($type == "object")
                $type = "mysqli_object";

            $return_type = strtoupper($return_type);

            if(!@$opt['catch'])
                $this->oop_exception("invalid return type received from query. Got [<b>$type</b>] instead of [<b>$return_type</b>]");

            return match($return_type) {
                default => [],
                'STRING' => '',
                'BOOL' => false,
                'INT' => 0,
            };
        }

        return $this->saved_result;
    }

    final public function get_result() : mixed {
        return $this->saved_result ?? null;
    }

    final public function uuid() : string {
        return $this->query("SELECT UUID()")[0];
    }

    final public function last_item(?string $column_to_check = null) : array {
        $d = $this->get_vars();
        $d['can_be_null'] = false;
        $d['clause'] = $d['clause'] ?? "";
        $d['columns'] = $d['columns'] ?? $d['values'] ?? "*";

        if(!isset($d['table']))
            $this->oop_exception("You did not initialize the `table`. Use the `->table(String)` method like this: `->value('your_table_name')`");

        $column_to_check = $column_to_check ?? $d['table'] . "." . $column_to_check;

        return $this->capture_result(
            [$this->query("SELECT {$d['columns']} FROM {$d['table']} {$d['clause']} ORDER BY $column_to_check DESC LIMIT 1", $d), $d],
        );
    }

    final public function insert(?array $column_and_values = null) : bool {
        $d = $this->get_vars();
        $column_and_values = $column_and_values ?? $d['values'] ?? $d['columns'];
        $table = $d['table'] ?? null;

        if(empty($table))
            $this->oop_exception("You did not initialize the `table`. Use the `->table(String)` method like this: `->value('your_table_name')`");

        if(is_array($column_and_values)){
            $cols = "";
            try {
                foreach ($column_and_values as $k => $c){
                    $c = SQL::instance()->clean($c, 11, 'PREVENT_SQL_INJECTION');

                    if(!str_ends_with($c . "",")") && $c !== null)
                        $c = "'$c'";

                    $cols .= $c == null ? "`$k`=NULL," : "`$k`=$c,";
                }
            }catch (\Exception $e){
                $this->oop_exception("Error occurred when trying to insert into a DB: $e");
            }
            $column_and_values = rtrim($cols,",");
        }

        $d['query_type'] = "INSERT";

        return $this->capture_result(
            [$this->query("INSERT INTO `$table` SET $column_and_values",$d) ?? false, $d],
            'bool',
        );
    }

    final public function insert_raw() : bool {
        $d = $this->get_vars();
        $columns = $d['columns'] ?? null;
        $values = $d['values'] ?? null;
        $clause = $d['clause'] ?? null;
        $table = $d['table'] ?? null;

        if(empty($columns))
            $this->oop_exception("You did not initialize the `columns`. Use the `->column(String)` method like this: `->column('id,name')`");

        if(empty($values))
            $this->oop_exception("You did not initialize the `values`. Use the `->value(String)` method. Example: `->value(\"(1, 'user name'), (2, 'another user name')\")`");

        if(empty($table))
            $this->oop_exception("You did not initialize the `table`. Use the `->table(String)` method like this: `->value('your_table_name')`");

        $columns = rtrim($columns,",");

        if(str_starts_with($values,"("))
            $values = "VALUES" . rtrim($values, ",");

        $d['query_type'] = "INSERT";

        return $this->capture_result(
            [$this->query("INSERT INTO `$table` ($columns) $values $clause",$d) ?? false, $d],
            'bool'
        );
    }

    final public function edit() : bool {
        $d = $this->get_vars();
        $values = $d['values'] ?? $d['columns'] ?? "NOTHING";
        $clause = $d['clause'] ?? null;
        $table = $d['table'] ?? null;

        if($values === "NOTHING")
            $this->oop_exception("There's nothing to update, please use the `column` or `value` method to rectify pass the columns to be updated");

        if(empty($table))
            $this->oop_exception("You did not initialize the `table`. Use the `->table(String)` method like this: `->value('your_table_name')`");

        if(is_array($values)){
            $cols = "";
            try {
                foreach ($values as $k => $c) {
                    $c = SQL::instance()->clean($c, 11, 'PREVENT_SQL_INJECTION');
                    $cols .= $c == null ? "`$k`=NULL," : "`$k`='$c',";
                }
            }catch (\Exception $e){
                $this->oop_exception("Error occurred when trying to update a DB: $e");
            }
            $values = rtrim($cols,",");
        }

        if(!empty(@$d['switch'])){
            $case_value = "";
            $clause = !$clause ? "" : $clause . " AND ";

            foreach ($d['switch'] as $k => $match){
                $case = "";
                $case_list = "";
                foreach ($d['case'][$k] as $j => $c){
                    $case .= "WHEN '$j' THEN $c ";
                    $case_list .= "'$j',";
                }

                $case_list = "(" . rtrim($case_list, ",") . ")";
                $case_value .= "`{$match['column']}` = CASE `{$match['switch']}` $case END,";

                $clause .= " `{$match['switch']}` IN $case_list AND";
            }

            $values = $values . ",";
            $values .= rtrim($case_value, ",");
            $clause = rtrim($clause," AND");
        }

        $d['query_type'] = "update";

        return $this->capture_result(
            [$this->query("UPDATE $table SET $values $clause", $d), $d],
            'bool'
        );
    }

    final public function select() : ?array {
        $d = $this->get_vars();
        $table = $d['table'] ?? null;
        $sort = $d['sort'] ?? null;
        $limit = $d['limit'] ?? null;
        $clause = $d['clause'] ?? "";
        $cols = $d['values'] ?? $d['columns'] ?? "*";
        $d['query_type'] = "SELECT";
        $d['fetch_as'] ??= "assoc";

        if(empty($table))
            $this->oop_exception("You did not initialize the `table`. Use the `->table(String)` method like this: `->value('your_table_name')`");

        if($sort){
            $str = "";

            foreach ($sort as $s){
                $str .= $s['sort'] . " " . $s['type'] . ", ";
            }

            $clause .= " ORDER BY " . rtrim($str, ", ");
        }

        if($limit) {
            $current_queue = $limit['index'];
            $result_per_queue = $limit['max_result'];

            // cut off request if we've gotten to the last record set
            if($current_queue > ceil($this->open($table)->clause($clause)->count_row("*") / $result_per_queue))
                return @$d['can_be_null'] ? null : [];

            $current_result = (max($current_queue, 1) - 1) * $result_per_queue;

            $clause .= " LIMIT $current_result, $result_per_queue";
        }

        if(!isset($d['join']))
            return $this->capture_result(
                [$this->query("SELECT $cols FROM $table $clause", $d), $d]
            );

        $join_query = "";

        foreach ($d['join'] as $k => $joint){
            $on = $d['on'][$k];
            $join = [
                "table" => $joint['table'],
                "type" => match (strtolower($joint['type'] ?? "")) {
                    "left", "inner", "right" => strtoupper($joint['type']),
                    default => "",
                },
                "on" => [$on['child_table'],$on['parent_table']],
            ];

            $join_query .= "{$join['type']} JOIN {$join['table']} ON {$join['on'][0]} = {$join['on'][1]} ";
        }

        $clause = $join_query . $clause;

        return $this->capture_result(
            [$this->query("SELECT $cols FROM $table $clause", $d), $d]
        );
    }

    final public function count_row(?string $column = null, ?string $WHERE = null) : int {
        $d = $this->get_vars();
        $col = $column ?? $d['values'] ?? $d['columns'] ?? "*";
        $WHERE = $WHERE ? "WHERE $WHERE" : ($d['clause'] ?? null);
        $table = $d['table'] ?? null;

        if(empty($table))
            $this->oop_exception("You did not initialize the `table`. Use the `->table(String)` method like this: `->value('your_table_name')`");
        
        $d['query_type'] = "COUNT";

        return $this->capture_result(
            [$this->query("SELECT COUNT($col) FROM $table $WHERE", $d), $d],
            'int'
        );
    }

    final public function delete(?string $WHERE = null) : bool {
        $d = $this->get_vars();
        $d['clause'] = $WHERE ? "WHERE $WHERE" : $d['clause'];
        $d['query_type'] = "DELETE";
        $table = $d['table'] ?? null;

        if(empty($table))
            $this->oop_exception("You did not initialize the `table`. Use the `->table(String)` method like this: `->value('your_table_name')`");

        return $this->capture_result(
            [$this->query("DELETE FROM $table {$d['clause']}", $d), $d],
            'bool'
        );
    }


}
