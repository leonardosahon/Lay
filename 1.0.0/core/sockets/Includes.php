<?php
declare(strict_types=1);
namespace Lay\core\sockets;
use Lay\core\Exception;
use Lay\libs\ObjectHandler;

trait Includes{
    private static array $INC_VARS = ["META" => null, "PAINT" => null];
    private static array $INC_CUSTOM_ROUTE = [];

    public static function set_inc_vars(array $vars) : void {
        self::$INC_VARS = array_replace_recursive(self::$INC_VARS, array_replace_recursive(self::$INC_VARS, $vars));
    }
    public static function get_inc_vars() : array{
        return self::$INC_VARS;
    }

    public function inc_file_as_string(string $file_location,$meta = [],$local = [],array $local_raw = []) : string {
        $layConfig = self::instance();
        ob_start(); include $file_location; return ob_get_clean();
    }

    public function inc_file_as_fun(\Closure $callback,...$args) : string {
        ob_start(); $callback(...$args); return ob_get_clean();
    }

    /**
     * @param array $route_list [key <String> => value <Array>]
     * value = [route location, file extension]
     * E.G = ["res/server/controller/__back/members/", ".php"]
     * @return void
     */
    public function inc_file_add_route(array $route_list) : void {
        foreach ($route_list as $k => $v){
            self::$INC_CUSTOM_ROUTE[$k] = $v;
        }
    }
    public function inc_file_get_route(string $route_key) : string {
        $route = @self::$INC_CUSTOM_ROUTE[$route_key];

        if(empty($route))
            Exception::throw_exception("Trying to access a custom route doesn't exist. $route_key","ROUTE::ERR");

        return $route[0];
    }

    public function inc_file(?string $file, string $type = "inc", bool $once = true, bool $strict = true, ?array $vars = []) : ?string {
        self::is_init();
        $using_custom_route = false;
        $slash = DIRECTORY_SEPARATOR;

        $inc_root = $this->get_res__server('inc');
        $ctrl_root = $this->get_res__server('ctrl');
        $view_root = $this->get_res__server('view');

        $default_routes = fn($side) => [
            "inc_$side" => [$inc_root . "__$side" . $slash,".inc"],
            "ctrl_$side" => [$ctrl_root . "__$side" . $slash,".php"],
            "view_$side" => [$view_root . "__$side" . $slash,".view"],
        ];

        if(self::$DEFAULT_ROUTE_SET === false && self::$USE_DEFAULT_ROUTE) {
            self::$INC_CUSTOM_ROUTE = array_merge(self::$INC_CUSTOM_ROUTE, $default_routes('back'), $default_routes('front'));
            self::$DEFAULT_ROUTE_SET = true;
        }

        foreach (self::$INC_CUSTOM_ROUTE as $k => $v){
            if($type == $k) {
                $type_loc = $v[0];
                $type = $v[1] ?? ".php";
                $using_custom_route = true;
                break;
            }
        }

        if(!$using_custom_route)
            switch ($type) {
                default:
                    $type_loc = $inc_root;
                    $type = ".inc";
                    break;
                case "ctrl":
                    $type_loc = $ctrl_root;
                    $type = ".php";
                    break;
                case "view":
                    $type_loc = $view_root;
                    $type = ".view";
                    break;
            }

        $file = $type_loc . $file . $type;
        $var = array_replace_recursive($vars, array_replace_recursive(self::get_inc_vars(), $vars));
        $obj = ObjectHandler::instance();

        $meta = $var['META'] ?? [];
        $local = $var['LOCAL'] ?? [];
        $local_raw = $var['LOCAL_RAW'] ?? [];
        if(self::$USE_OBJS){
            $meta = $obj->to_object($meta);
            $local = $obj->to_object($local);
        }

        if(!file_exists($file) && $strict)
            Exception::throw_exception("execution Failed trying to include file ($file)","File-Not-Found");

        if(isset($vars['INCLUDE_AS_STRING']) && $vars['INCLUDE_AS_STRING'])
            return $this->inc_file_as_string($file,$meta,$local,$local_raw);

        $layConfig = $this;

        $once ? include_once $file : include $file;
        return null;
    }
}
