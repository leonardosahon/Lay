<?php
declare(strict_types=1);
namespace Lay\libs;
use Lay\core\sockets\IsSingleton;
use Lay\orm\SQL;

class ObjectHandler {
    use IsSingleton;

    /**
     * @param bool $strict [default = true] throws error if nothing is found in request POST request
     * @param bool $return_array
     * @return object|bool|null|array
     */
    public function get_json(bool $strict = true, bool $return_array = false): object|bool|null|array
    {
        // TODO: Come up with a solution to work around processing post requests with
        $x = file_get_contents("php://input");
        $msg = "No values found in request; check if you actually sent your values as \$_POST";
        $post = (object) $_POST;
        if(!empty($x) && !str_starts_with($x, "{")) {
            $x = "";
            $msg = "JSON formatted \$_POST needed; but invalid JSON format was found";
        }
        if($strict && empty($x) && empty($post)) SQL::instance()->use_exception(
            "ObjectHandler::ERR::get_json",
            "<div style='color: #eeb300; font-weight: bold; margin: 5px 1px;'>$msg</div>");
        return json_decode($x, $return_array) ?? $post;
    }
    public function to_object($array) : object {
        if(is_object($array))
            return $array;

        $obj = new \stdClass();
        foreach ($array as $k => $v){
            if(is_array($v)) {
                $obj->{$k} = $this->to_object($v);
                continue;
            }
            $obj->{$k} = $v;
        }

        return $obj;
    }
}