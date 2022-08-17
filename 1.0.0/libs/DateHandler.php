<?php
declare(strict_types=1);
namespace Lay\libs;

class DateHandler {
    private static DateHandler $instance;
    private function __construct(){}
    private function __clone(){}

    public static function instance() : self {
        if(!isset(self::$instance))
            self::$instance = new DateHandler();
        return self::$instance;
    }
    public function date(?string $datetime = null, int $level = 10, string $format = "Y-m-d H:i:s") : string {
        $datetime = $datetime ?: date("Y-m-d H:i:s");
        switch ($level){
            case 0: $format = "H:i:s"; break;
            case 1: $format = "Y-m-d"; break;
            case 2: $format = "D d, M Y | h:i a"; break;
            default: break;
        }
        return date($format, strtotime($datetime));
    }
}