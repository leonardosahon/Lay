<?php
declare(strict_types=1);
namespace Lay\orm\EXTENSIONS;

use Closure;
use Lay\orm\SQL;

trait SelectorOOP {
    private static int $current_index = 0;
    private array $cached_options = [];

    private function get_vars() : array {
        $r = $this->cached_options[self::$current_index];
        unset($this->cached_options[self::$current_index]);
        self::$current_index -= 1;

        if(empty($r))
            $this->oop_exception("No variable passed to ORM. At least `table` should be passed");

        return $r;
    }
    private function store_vars(string $key, mixed $value, $id1 = null, $id2 = null) : self {
        $index = max(self::$current_index,0);

        if($id1 === true)
            $this->cached_options[$index][$key][] = $value;

        elseif($id1 && !$id2)
            $this->cached_options[$index][$key][$id1] = $value;

        elseif($id1 && $id2)
            $this->cached_options[$index][$key][$id1][$id2] = $value;

        else
            $this->cached_options[$index][$key] = $value;

        return $this;
    }

    /**
     * @deprecated use open instead
     * @removed
     */
    final public function op(?string $table = null) : self {
        self::$current_index++;

        if($table)
            $this->table($table);

        return $this;
    }
    final function open(string $table) : self {
        self::$current_index++;

        if($table)
            $this->table($table);

        return $this;
    }
    final public function table(string $table) : self {
        return $this->store_vars('table',$table);
    }
    final public function column(string|array $cols) : self {
        return $this->store_vars('columns',$cols);
    }
    final public function value(string|array $values) : self {
        return $this->store_vars('values',$values);
    }
    final public function switch(string $switch_id, string $column_for_condition,string $column_for_assignment) : self {
        return $this->store_vars('switch',["switch" => $column_for_condition, "column" => $column_for_assignment],$switch_id);
    }
    final public function case(string $switch_id,string $when_column_for_condition_is, string $then_column_for_assignment_is) : self {
        return $this->store_vars('case',$then_column_for_assignment_is,$switch_id,$when_column_for_condition_is);
    }
    final public function join(string $join_table, string $type = "") : self {
        return $this->store_vars('join',["table" => $join_table, "type" => $type,],true);
    }
    final public function on(string $col_from_child_table, string $col_from_parent_table) : self {
        return $this->store_vars('on',["child_table" => $col_from_child_table, "parent_table" => $col_from_parent_table],true);
    }
    final public function except(string $comma_separated_columns) : self {
        return $this->store_vars('except',$comma_separated_columns);
    }
    final public function clause(string $clause) : self {
        return $this->store_vars('clause',$clause);
    }
    final public function fun(Closure $function) : self {
        return $this->store_vars('fun',$function);
    }
    final public function debug() : self {
        return $this->store_vars('debug',1);
    }
    final public function catch() : self {
        return $this->store_vars('catch',1);
    }
    final public function no_null() : self {
        return $this->store_vars('can_be_null',false);
    }
    final public function no_false() : self {
        return $this->store_vars('can_be_false',false);
    }
    final public function assoc() : self {
        return $this->store_vars('fetch_as','assoc');
    }
    final public function row() : self {
        return $this->store_vars('fetch_as','row');
    }
    final public function loop() : self {
        return $this->store_vars('loop',1);
    }
    final public function not_empty() : self {
        $this->no_null();
        return $this->no_false();
    }
    final public function loop_assoc(?string $clause = null) : ?array {
        if($clause) $this->clause($clause);
        $this->loop();
        $this->assoc();
        return $this->select();
    }
    final public function loop_row(?string $clause = null) : ?array {
        if($clause) $this->clause($clause);
        $this->loop();
        $this->row();
        return $this->select();
    }
    final public function then_insert(string|array $columns) : bool {
        $this->column($columns);
        return $this->insert();
    }
    final public function then_update(string $clause) : bool {
        $this->clause($clause);
        return $this->edit();
    }
    final public function then_select(string $clause) : array {
        $this->clause($clause);
        $this->no_null();
        $this->assoc();
        return $this->select();
    }

    final public function uuid() : string {
        return $this->query("SELECT UUID()")[0];
    }

    final public function last_item(string $column_to_check) : array {
        $d = $this->get_vars();
        $d['can_be_null'] = false;
        $d['clause'] = $d['clause'] ?? "";
        $d['columns'] = $d['columns'] ?? $d['values'] ?? "*";

        return $this->query("SELECT {$d['columns']} FROM {$d['table']} {$d['clause']} ORDER BY $column_to_check DESC LIMIT 1", $d);
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

                    if(!str_ends_with($c . "",")"))
                        $c = "'$c'";

                    $cols .= $c == null ? "`$k`=NULL," : "`$k`=$c,";
                }
            }catch (\Exception $e){
                $this->oop_exception("Error occurred when trying to insert into a DB: $e");
            }
            $column_and_values = rtrim($cols,",");
        }

        $d['query_type'] = "INSERT";
        return $this->query("INSERT INTO `$table` SET $column_and_values",$d) ?? false;
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
        return $this->query("INSERT INTO `$table` ($columns) $values $clause",$d) ?? false;
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
        return $this->query("UPDATE $table SET $values $clause", $d);
    }

    final public function select() : ?array {
        $d = $this->get_vars();
        $table = $d['table'] ?? null;
        $clause = @$d['clause'];
        $cols = $d['values'] ?? $d['columns'] ?? "*";
        $d['query_type'] = "SELECT";

        if(empty($table))
            $this->oop_exception("You did not initialize the `table`. Use the `->table(String)` method like this: `->value('your_table_name')`");

        if(!isset($d['join']))
            return $this->query("SELECT $cols FROM $table $clause", $d);

        $join = [];
        $join_query = "";

        foreach ($d['join'] as $k => $joint){
            $on = $d['on'][$k];
            $join[] = [
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

        return $this->query("SELECT $cols FROM $table $clause", $d);
    }

    final public function count_row(?string $column = null, ?string $WHERE = null) : int {
        $d = $this->get_vars();
        $col = $column ?? $d['values'] ?? $d['columns'] ?? "NOTHING";
        $WHERE = $WHERE ? "WHERE $WHERE" : ($d['clause'] ?? null);
        $table = $d['table'] ?? null;

        if(empty($table))
            $this->oop_exception("You did not initialize the `table`. Use the `->table(String)` method like this: `->value('your_table_name')`");

        if($col === "NOTHING")
            $this->oop_exception("No column to count");

        $d['query_type'] = "COUNT";
        return $this->query("SELECT COUNT($col) FROM $table $WHERE", $d);
    }

    final public function delete(?string $WHERE = null) : bool {
        $d = $this->get_vars();
        $d['clause'] = $WHERE ? "WHERE $WHERE" : $d['clause'];
        $d['query_type'] = "DELETE";
        $table = $d['table'] ?? null;

        if(empty($table))
            $this->oop_exception("You did not initialize the `table`. Use the `->table(String)` method like this: `->value('your_table_name')`");

        return $this->query("DELETE FROM $table {$d['clause']}", $d);
    }

    private function oop_exception(string $message) : void {
        $this->use_exception("SQL_OOP::ERR", $message);
    }
}