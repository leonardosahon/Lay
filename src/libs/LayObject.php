<?php
declare(strict_types=1);

namespace Lay\libs;

use Lay\core\traits\IsSingleton;
use Lay\orm\SQL;
use stdClass;

class LayObject
{
    use IsSingleton;

    /**
     * @param bool $strict [default = true] throws error if nothing is found in request POST request
     * @param bool $return_array
     * @return mixed Returns what json_decode returns
     * @throws \Exception
     */
    public function get_json(bool $strict = true, bool $return_array = false): mixed
    {
        $x = file_get_contents("php://input");
        $msg = "No values found in request; check if you actually sent your values as \$_POST";
        $post = $return_array ? $_POST : (object)$_POST;

        if (!empty($x) && !str_starts_with($x, "{")) {
            $x = "";
            $msg = "JSON formatted \$_POST needed; but invalid JSON format was found";
        }

        if ($strict && empty($x) && empty($post))
            SQL::instance()->use_exception(
                "ObjectHandler::ERR::get_json",
                "<div style='color: #eeb300; font-weight: bold; margin: 5px 1px;'>$msg</div>"
            );

        return json_decode($x, $return_array) ?? $post;
    }

    /**
     * @param array|object $array array to be converted
     * @return object
     */
    public function to_object(array|object $array): object
    {
        if (is_object($array))
            return $array;

        $obj = new stdClass();

        foreach ($array as $k => $v) {
            if (is_array($v)) {
                $obj->{$k} = $this->to_object($v);
                continue;
            }
            $obj->{$k} = $v;
        }

        return $obj;
    }

    /**
     * @param string $token JWT token
     * @param bool $assoc_array return the object as an associative array
     * @return mixed Returns what json_decode returns
     */
    public function jwt_decode(string $token, bool $assoc_array = false): mixed
    {
        return json_decode(
            base64_decode(
                str_replace(
                    '_',
                    '/',
                    str_replace(
                        '-',
                        '+',
                        explode('.', $token)[1]
                    )
                )
            ),
            $assoc_array
        );
    }
}
