<?php
declare(strict_types=1);
namespace Lay\libs;

use DateTime;
use Lay\core\traits\IsSingleton;

class LayDate {
    use IsSingleton;

    public static function date(?string $datetime = null, int $level = 10, string $format = "Y-m-d H:i:s", bool $figure = false) : string|int {
        $datetime = $datetime ?: date("Y-m-d H:i:s");

        switch ($level){
            case 0: $format = "H:i:s"; break;
            case 1: $format = "Y-m-d"; break;
            case 2: $format = "D d, M Y | h:i a"; break;
            default: break;
        }

        if($figure)
            return strtotime($datetime);

        return date($format, strtotime($datetime));
    }

    public static function elapsed($current_time): string
    {
        $now = new DateTime;
        $ago = new DateTime($current_time);
        $diff = $now->diff($ago);

        $week = floor($diff->d / 7);
        $diff->d -= $week * 7;

        $string = [
            'y' => 'year',
            'm' => 'month',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        ];

        if($week > 1)
            return self::date($current_time, 3, "d M o [h:ia]");

        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
                continue;
            }

            unset($string[$k]);            
        }

        $string = array_slice($string, 0, 2);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
}