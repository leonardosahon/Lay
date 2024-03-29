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
    final public function op(?string $table = null) : self {
        self::$current_index++;

        if($table)
            $this->table($table);

        return $this;
    }
    final function open(string $table) : self {
        return $this->op($table);
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
        return $this->store_vars('function',$function);
    }
    final public function debug() : self {
        return $this->store_vars('debug',1);
    }
    final public function no_null() : self {
        return $this->store_vars('no_null',"!");
    }
    final public function no_false() : self {
        return $this->store_vars('no_false','~');
    }
    final public function assoc() : self {
        return $this->store_vars('fetch_as','assoc');
    }
    final public function row() : self {
        return $this->store_vars('fetch_as','row');
    }
    final public function loop() : self {
        return $this->store_vars('loop','loop');
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

    /** @see SQL_CORE::query_insert() */
    final public function insert() : bool {
        $d = $this->get_vars();

        if(is_array(@$d['columns'])){
            $cols = "";
            try {
                foreach ($d['columns'] as $k => $c){
                    $c = SQL::instance()->clean($c, 11, 'PREVENT_SQL_INJECTION');
                    $cols .= $c == null ? "`$k`=NULL," : "`$k`='$c',";
                }
            }catch (\Exception $e){
                SQL::instance()->use_exception("LAY_ORM_ERR", "Error occurred when trying to insert into a DB: $e");
            }
            $d['columns'] = rtrim($cols,",");
        }

        if(is_array(@$d['values'])){
            $values = "";
            try{
                foreach ($d['values'] as $k => $c){
                    $c = SQL::instance()->clean($c, 11, 'PREVENT_SQL_INJECTION');
                    $values .= $c == null ? "`$k`=NULL," : "`$k`='$c',";
                }
            }catch (\Exception $e){
                SQL::instance()->use_exception("LAY_ORM_ERR", "Error occurred when trying to insert into a DB: $e");
            }
            $d['values'] = rtrim($values,",");
        }

        $cols = @$d['columns'];
        $values = @$d['values'];

        if($cols == "*")
            $cols = $values;

        return $this->query_insert(@$d['table'],$cols,$values,@$d['debug']) ?? false;
    }
    /** @see SQL_CORE::query_update() */
    final public function edit() : bool {
        $d = $this->get_vars();
        $values = $d['values'] ?? $d['columns'] ?? "NOTHING";

        if($values === "NOTHING")
            $this->oop_exception();

        if(is_array($values)){
            $cols = "";
            try {
                foreach ($values as $k => $c) {
                    $c = SQL::instance()->clean($c, 11, 'PREVENT_SQL_INJECTION');
                    $cols .= $c == null ? "`$k`=NULL," : "`$k`='$c',";
                }
            }catch (\Exception $e){
                SQL::instance()->use_exception("LAY_ORM_ERR", "Error occurred when trying to update a DB: $e");
            }
            $values = rtrim($cols,",");
        }

        if(!empty(@$d['switch'])){
            $switch = [];
            foreach ($d['switch'] as $k => $match){
                $switch[] = [
                    "switch" => $match['switch'],
                    "column" => $match['column'],
                    "case" => $d['case'][$k]
                ];
            }
            $values = [
                "values" => $values,
                "match" => $switch
            ];
        }
        return $this->query_update($d['table'],$values,@$d['clause'],@$d['debug'],@$d['no_false']);
    }

    /** @see SQL_CORE::query_select() */
    final public function select() : ?array {
        $d = $this->get_vars();
        $table = $d['table'];
        $clause = @$d['clause'];
        $cols = $d['values'] ?? $d['columns'] ?? "*";
        $opts = [@$d['fetch_as'],@$d['loop'],@$d['no_null'],@$d['debug'],["fun" => @$d['function']],["except" => @$d['except']]];

        if(!isset($d['join']))
            return $this->query_select($cols, $table, $clause, $opts);

        $join = [];
        foreach ($d['join'] as $k => $joint){
            $on = $d['on'][$k];
            $join[] = [
                "table" => $joint['table'],
                "type" => @$joint['type'],
                "on" => [$on['child_table'],$on['parent_table']],
            ];
        }
        return $this->query_select([
            "cols" => $cols,
            "table" => $table,
            "clause" => $clause,
            "join" => $join,
        ],$opts);
    }
    /** @see SQL_CORE::query_count() */
    final public function count_row() : int {
        $d = $this->get_vars();
        $col = $d['values'] ?? $d['columns'] ?? "NOTHING";

        if($col === "NOTHING")
            $this->oop_exception();

        return $this->query_count($col,$d['table'],@$d['clause'],@$d['debug']);
    }
    /** @see SQL_CORE::query_delete() */
    final public function delete() : bool {
        $d = $this->get_vars();
        return $this->query_delete($d['table'],$d['clause'],(int) @$d['debug']);
    }

    private function oop_exception() : void {
        $this->use_exception("SQL_OOP::ERR", "No columns to update was passed, please use the `column` or `value` method to rectify this");
    }
}