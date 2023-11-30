<?php
declare(strict_types=1);
namespace Lay\libs;

use DateTime;
use Lay\core\traits\IsSingleton;

class LayDate {
    use IsSingleton;

    /**
     * @param string|int|null $datetime values accepted by `strtotime` or integer equivalent of a datetime
     * @param string $format a valid `datetime` format
     * @param int $format_index 0 = date; 1 = time; 2 = appearance: [Ddd dd, Mmm YYYY | hh:mm a] - format: [D d, M Y | h:i a]
     * @param bool $figure to return the integer equivalent of the give datetime
     * @return string|int
     */
    public static function date(string|int|null $datetime = null, string $format = "Y-m-d H:i:s", int $format_index = 3, bool $figure = false) : string|int {

        $format = match ($format_index) {
            0 => "Y-m-d",
            1 => "H:i:s",
            2 => "D d, M Y | h:i a",
            default => $format,
        };

        if(is_int($datetime))
            return date($format, $datetime);

        $datetime = $datetime ?: date($format);

        if($figure)
            return strtotime($datetime);

        return date($format, strtotime($datetime));
    }

    public static function elapsed(string $current_time, int $depth = 1, string $format = "M d, o"): string
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
            return self::date($current_time, 3, $format);

        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
                continue;
            }

            unset($string[$k]);            
        }

        $string = array_slice($string, 0, $depth);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
}