<?php
declare(strict_types=1);
namespace Lay\orm\EXTENSIONS;

trait SelectorProcedure {
    /** @see SQL_CORE::query_insert() */
    final public function add(string $table,...$option):bool{return$this->query_insert($table,$option) ?? false;}
    /** @see SQL_CORE::query_select() */
    final public function get(string $cols,?string $table=null,...$opt):?array{return$this->query_select($cols,$table,$opt);}
    /** @see SQL_CORE::query_select() */
    final public function get_join(array $query_array,...$opt):?array{return$this->query_select($query_array,$opt);}
    /** @see SQL_CORE::query_count() */
    final public function count(string $col,?string $table=null,...$opt):int{return$this->query_count($col,$table,$opt);}
    /** @see SQL_CORE::query_update() */
    final public function update(string $table,string $vals,string $clause,int $debug=0):bool{return$this->query_update($table,$vals,$clause,$debug);}
    /** @see SQL_CORE::query_update() */
    final public function update_multi(string $table,array $array,string $clause,int $debug=0):bool{return$this->query_update($table,$array,$clause,$debug);}
    /** @see SQL_CORE::query_delete() */
    final public function del(string $table,string $where,int $debug=0):bool{return$this->query_delete($table,$where,$debug);}
}