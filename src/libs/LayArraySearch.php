<?php

namespace Lay\libs;

class LayArraySearch
{
    /**
     * Enhanced array search, this will search for values even in multiple dimensions of arrays.
     * @param mixed $needle
     * @param array $haystack
     * @param bool $strict choose between == or === comparison operator
     * @param array $__RESULT_INDEX__ ***Do not modify this option, it is readonly to the developer***
     * @return string[] <p>Returns the first occurrence of the value in an array that contains the value
     * as interpreted by the function and the keys based on the total dimension it took to find the value.</p>
     * <code>::run("2", ["ss", [[2]], '2'], true) </code>
     * <code>== ['value' => '2', index => [1,2]]</code>
     *
     * <code>::run("2", ["ss", [[2]], '2']) </code>
     * <code>== ['value' => '2', index => [1,0,0]]</code>
     */
    final public static function run(mixed $needle, array $haystack, bool $strict = false, array $__RESULT_INDEX__ = []) : array {
        $result = [
            "value" => "LAY_NULL",
            "index" => $__RESULT_INDEX__,
            "found" => false,
        ];

        foreach ($haystack as $i => $d) {
            if(is_array($d)) {
                $result['index'][] = $i;
                $search = self::run($needle, $d, $strict, $result['index']);

                if($search['value'] !== "LAY_NULL") {
                    $result = array_merge($result,$search);
                    break;
                }
                continue;
            }

            if(($strict === false && $needle == $d)){
                $result['index'][] = $i;
                $result['value'] = $d;
                $result['found'] = true;
                break;
            }

            if(($strict === true && $needle === $d)){
                $result['index'][] = $i;
                $result['value'] = $d;
                $result['found'] = true;
                break;
            }
        }

        return $result;
    }
}