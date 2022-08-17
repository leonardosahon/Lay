<?php
declare(strict_types=1);
namespace Lay\orm\EXTENSIONS;

use Closure;
trait SelectorOOP {
    private static int $current_index = 0;
    private array $cached_options = [];

    private function get_vars() : array {
        $r = $this->cached_options[self::$current_index];
        unset($this->cached_options[self::$current_index]);
        self::$current_index -= 1;
        return $r;
    }
    private function store_vars($key, $value, $id1 = null, $id2 = null) : self {
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
    final public function op() : self {
        self::$current_index++;
        return $this;
    }
    final public function table(string $table) : self {
        return $this->store_vars('table',$table);
    }
    final public function column(string $cols) : self {
        return $this->store_vars('columns',$cols);
    }
    final public function value(string $values) : self {
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

    /** @see SQL_CORE::query_insert() */
    final public function insert() : bool {
        $d = $this->get_vars();
        $cols = @$d['columns'];
        $values = @$d['values'];

        if($cols == "*")
            $cols = $values;

        return $this->query_insert(@$d['table'],$cols,$values,@$d['debug']) ?? false;
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
        $col = $d['values'] ?? $d['columns'];
        return $this->query_count($col,$d['table'],@$d['clause'],@$d['debug']);
    }
    /** @see SQL_CORE::query_update() */
    final public function edit() : bool {
        $d = $this->get_vars();
        $values = $d['values'] ?? $d['columns'];
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
    /** @see SQL_CORE::query_delete() */
    final public function delete() : bool {
        $d = $this->get_vars();
        return $this->query_delete($d['table'],$d['clause'],(int) @$d['debug']);
    }
}