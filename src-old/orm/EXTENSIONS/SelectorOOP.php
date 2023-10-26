<?php
declare(strict_types=1);
namespace Lay\orm\EXTENSIONS;

use Closure;
use JetBrains\PhpStorm\ExpectedValues;
use Lay\orm\SQL;
use PHPUnit\Framework\Attributes\Depends;

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

    #[Depends('on')]
    final public function join(string $join_table, #[ExpectedValues(['right', 'inner', 'left', ''])] string $type = "") : self {
        return $this->store_vars('join',["table" => $join_table, "type" => $type,],true);
    }

    #[Depends('join')]
    final public function on(string $col_from_child_table, string $col_from_parent_table) : self {
        return $this->store_vars('on',["child_table" => $col_from_child_table, "parent_table" => $col_from_parent_table],true);
    }
    final public function except(string $comma_separated_columns) : self {
        return $this->store_vars('except',$comma_separated_columns);
    }
    final public function clause(string $clause) : self {
        return $this->store_vars('clause',$clause);
    }
    final public function where(string $WHERE) : self {
        return $this->clause("WHERE $WHERE");
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
    final public function sort(string $sort, #[ExpectedValues(['ASC', 'asc', 'DESC', 'desc'])] string $order = "ASC") : self {
        return $this->store_vars('sort',["sort" => $sort, "type" => $order,],true);
    }

    /**
     * @param int $max_result Specify query result limit
     * @param int $page_number Specifies the page batch based on the limit
     * @param string|null $column_to_check
     * @return SelectorOOP|SQL
     */
    final public function limit(int $max_result, int $page_number = 1, ?string $column_to_check = null) : self {
        return $this->store_vars('limit',["index" => $page_number, "max_result" => $max_result, "column" => $column_to_check,]);
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
    final public function then_update(?string $clause = null) : bool {
        if($clause)
            $this->clause($clause);

        return $this->edit();
    }
    final public function then_select(?string $clause = null) : array {
        if($clause)
            $this->clause($clause);

        $this->no_null();
        $this->assoc();
        return $this->select();
    }

    use SelectorOOPCrud;

    private function oop_exception(string $message) : void {
        $this->use_exception("SQL_OOP::ERR", $message);
    }
}