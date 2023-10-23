<?php
declare(strict_types=1);
namespace Lay\libs;
use Lay\core\LayConfig;
use Lay\core\sockets\IsSingleton;

class LayGenId {
    use IsSingleton;
    private static int $recursion_index = 0;
    
    private static string $prepend;
    private static string $append;
    private static int $digit_length = 7;
    private static string $confirm_table;
    private static string $confirm_column;
    protected static function count(string $table, string $column, $value) : bool {
        $value = LayConfig::get_orm()->clean($value,16,'strict');
        return LayConfig::get_orm()->open($table)->count_row($column,"$column='$value'") > 0;
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
        self::$recursion_index++;
        
        $pre = self::$prepend ?? null;
        $end = self::$append ?? null;
        $length = self::$digit_length != 0 ?  self::$digit_length - 1 : 0;
        $table = self::$confirm_table ?? null;
        $column = self::$confirm_column ?? null;

        if(self::$recursion_index > 10)
            $length++;
        
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

    public function gen_str(?string ...$remove_chars) : ?string {
        self::$recursion_index++;
        $length = self::$digit_length;
        $table = self::$confirm_table ?? null;
        $column = self::$confirm_column ?? null;
        $pre = self::$prepend ?? null;
        $end = self::$append ?? null;
        
        if(self::$recursion_index > 10)
            $length++;
        
        $rand = str_replace($remove_chars, '', base64_encode($pre . md5(time() . "") . random_bytes($length) . $end));
        $rand = substr($rand,0,$length);

        if($table && $column && self::count($table,$column,$rand))
            return $this->gen_str(...$remove_chars);

        return $rand;
    }
}