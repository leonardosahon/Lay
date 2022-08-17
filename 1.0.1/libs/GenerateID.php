<?php
declare(strict_types=1);
namespace Lay\libs;
use Lay\orm\SQL;

class GenerateID {
    private static string $prepend;
    private static string $append;
    private static int $digit_length = 7;
    private static string $confirm_table;
    private static string $confirm_column;
    private static GenerateID $instance;
    private function __construct(){}
    private function __clone(){}
    protected static function count(string $table, string $column, $value) : bool {
        return SQL::instance()->count($column,$table,"WHERE $column='$value'") > 0;
    }

    public static function instance() : self {
        if(!isset(self::$instance))
            self::$instance = new GenerateID();
        return self::$instance;
    }

    public function digit(?int $digit_length = 7) : self {
        self::$digit_length = $digit_length;
        return $this;
    }
    public function prepend(?string $string = null) : self {
        self::$prepend = $string;
        return $this;
    }
    public function append(?string $string = null) : self {
        self::$append = $string;
        return $this;
    }
    public function db_confirm(string $confirm_table, string $confirm_column) : self {
        self::$confirm_table = $confirm_table;
        self::$confirm_column = $confirm_column;
        return $this;
    }

    public function gen() : ?string{
        $pre = self::$prepend ?? null;
        $end = self::$append ?? null;
        $length = self::$digit_length != 0 ?  self::$digit_length - 1 : 0;
        $table = self::$confirm_table ?? null;
        $column = self::$confirm_column ?? null;

        $min = 10 ** $length;
        $rand = rand($min, 9 * $min);

        if($pre)
            $rand = $pre . $rand;
        if($end)
            $rand = $rand . $end;

        if($table && $column && self::count($table,$column,$rand))
            return $this->digit($length)->prepend($pre)->append($end)->db_confirm($table, $column)->gen();
        return $rand . "";
    }
}