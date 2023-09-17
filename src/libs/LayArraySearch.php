<?php

namespace Lay\libs;

class LayArraySearch
{
    /**
     * Enhanced array search, this will search for values even in multiple dimensions of arrays
     * @param string $needle
     * @param array $haystack
     * @param bool $strict choose between == or === comparison operator
     * @param int $total_dimension ***Do not modify this option, it is readonly to the developer***
     * @return string[]
     */
    final public static function run(string $needle, array $haystack, bool $strict = false, int $total_dimension = 0) : array {
        $result = [
            "value" => "LAY_NULL",
        ];

        foreach ($haystack as $i => $d){
            if(is_array($d)){
                ++$total_dimension;
                $result['index_d' . $total_dimension] = $i;
                $search = self::run($needle, $d, $strict, $total_dimension);

                if($search['value'] !== "LAY_NULL") {
                    $result = array_merge($result,$search);
                    break;
                }
                --$total_dimension;
                continue;
            }

            if(($strict === false && $needle == $d)){
                $total_dimension++;
                $result['index_d' . $total_dimension] = $i;
                $result['value'] = $d;
                break;
            }

            if(($strict === true && $needle === $d)){
                $total_dimension++;
                $result['index_d' . $total_dimension] = $i;
                $result['value'] = $d;
                break;
            }
        }

        return $result;
    }
}