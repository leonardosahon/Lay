<?php
declare(strict_types=1);
namespace Lay\libs;

use DateTime;
use Lay\core\traits\IsSingleton;

class LayDate {
    use IsSingleton;

    /**
     * @param string|int|null $datetime values accepted by `strtotime` or integer equivalent of a datetime
     * @link https://php.net/manual/en/function.idate.php
     * @param string $format <p>
     *  <table>
     *  The following characters are recognized in the
     *  format parameter string
     *  <tr valign="top">
     *  <td>format character</td>
     *  <td>Description</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>B</td>
     *  <td>Swatch Beat/Internet Time</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>d</td>
     *  <td>Day of the month</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>h</td>
     *  <td>Hour (12 hour format)</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>H</td>
     *  <td>Hour (24 hour format)</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>i</td>
     *  <td>Minutes</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>I (uppercase i)</td>
     *  <td>returns 1 if DST is activated,
     *  0 otherwise</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>L (uppercase l)</td>
     *  <td>returns 1 for leap year,
     *  0 otherwise</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>m</td>
     *  <td>Month number</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>s</td>
     *  <td>Seconds</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>t</td>
     *  <td>Days in current month</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>U</td>
     *  <td>Seconds since the Unix Epoch - January 1 1970 00:00:00 UTC -
     *  this is the same as time</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>w</td>
     *  <td>Day of the week (0 on Sunday)</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>W</td>
     *  <td>ISO-8601 week number of year, weeks starting on
     *  Monday</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>y</td>
     *  <td>Year (1 or 2 digits - check note below)</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>Y</td>
     *  <td>Year (4 digits)</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>z</td>
     *  <td>Day of the year</td>
     *  </tr>
     *  <tr valign="top">
     *  <td>Z</td>
     *  <td>Timezone offset in seconds</td>
     *  </tr>
     *  </table>
     *  </p>
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

    public static function elapsed(string $current_time, int $depth = 1, string $format = "M d, o", bool $append_ago = true): string
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
            return self::date($current_time, $format);

        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
                continue;
            }

            unset($string[$k]);            
        }

        $string = array_slice($string, 0, $depth);
        return $string ? implode(', ', $string) . ($append_ago ? ' ago' : '') : 'just now';
    }
}