<?php
declare(strict_types=1);
namespace Lay\orm\EXTENSIONS;

trait OneLiner {
    public function change_db(string $dbName) : bool {
        return mysqli_select_db(self::core()->get_link(),self::core()->clean($dbName,20));
    }
    public function last_id(){
        return mysqli_insert_id(self::core()->get_link());
    }
    /**
     * @param $cols string columns to extract
     * @param $table string table to extract from
     * @param string $where default[id] auto incremented column
     * @param int $debug 1 if you wish to debug your query
     * @return array|null
     **/
    public function last_col(string $cols, string $table, string $where = 'id', int $debug = 0) : ?array {
        $id = ($where=='id') ? ("id=" . $this->last_id()) : ($where);
        return self::core()->query("SELECT $cols FROM $table WHERE $id", "last_insert", $debug);
    }
    /**
     * Get last value of a table's int column
     * @param string $table
     * @param string $column column to check for last value
     * @param string|null $clause condition for selection
     * @return int returns the last value of the column passed to the second parameter on the table passed on the first parameter
     */
    public function last_value(string $table, string $column = "id", ?string $clause = null) : int {
        $clause = $clause ?? "ORDER BY $column DESC LIMIT 1";
        if(empty($column)) self::core()->use_exception("Query Execution Error",
            "You need to specify a column to check for last_value");
        return (int) (self::core()->get($column,$table,$clause,'row','!')[0] ?? 0);
    }
}