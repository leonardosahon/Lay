<?php
declare(strict_types=1);
namespace Lay\core\traits;
use Lay\core\Exception;
use Lay\libs\LayObject;

trait Includes {
    private static array $INC_VARS = ["META" => null];
    private static array $INC_CUSTOM_ROUTE = [];

    public static function set_inc_vars(array $vars) : void {
        self::is_init();
        self::$INC_VARS = array_replace_recursive(self::$INC_VARS, array_replace_recursive(self::$INC_VARS, $vars));
    }

    public static function get_inc_vars() : array {
        self::is_init();
        return self::$INC_VARS;
    }

    public function inc_file_as_string(string $file_location, array|object $meta = [], array|object $local = [], array $local_array = []) : string {
        if(!file_exists($file_location))
            Exception::throw_exception("Execution Failed trying to include file ($file_location)","File-Not-Found");

        $view = is_array($meta) ? ($meta['view'] ?? null) : $meta?->view;

        $GLOBALS['meta'] = $meta;
        $GLOBALS['local'] = $local;
        $GLOBALS['local_array'] = $local_array;
        $GLOBALS['view'] = $view;

        $layConfig = self::instance();
        ob_start(); include $file_location; return ob_get_clean();
    }

    public function inc_file_as_fun(\Closure $callback,...$args) : string {
        self::is_init();
        ob_start(); $callback(...$args); return ob_get_clean();
    }

    /**
     * @param $route_list array
     * <tr><td>key (string)</td> <td>string key to access the route;</td></tr>
     * <tr><td>value (array)</td> <td>[route location, file extension];</td></tr>
     * <tr><td>Example:</td> <td>'member_inc' => ["res/server/includes/__back/members/", ".inc"]</td></tr>
     * <tr><td>Use case</td><td>LayConfig::instance()->inc_file("members_session_controller","member_inc")</td></tr>
     * @return void
     */
    public function inc_file_add_route(array $route_list) : void {
        self::is_init();
        foreach ($route_list as $k => $v){
            self::$INC_CUSTOM_ROUTE[$k] = $v;
        }
    }
    public function inc_file_get_route(string $route_key) : string {
        self::is_init();
        $route = @self::$INC_CUSTOM_ROUTE[$route_key];

        if(empty($route))
            Exception::throw_exception("Trying to access a custom route that doesn't exist. $route_key","ROUTE::ERR");

        return $route[0] ?? $route['root'];
    }

    public function inc_file(?string $file, string $type = "inc", bool $once = true, bool $strict = true, ?array $vars = []) : ?string {
        self::is_init();
        $using_custom_route = false;
        $slash = DIRECTORY_SEPARATOR;

        $server = self::res_server();
        $inc_root = $server->inc;
        $view_root = $server->view;
        $type_loc = $inc_root;

        $default_routes = fn($side) => [
            "inc_$side" => [
                'root' => $inc_root . "__$side" . $slash,
                'ext' => ".inc"
            ],
            "view_$side" => [
                'root' => $view_root . "__$side" . $slash,
                'ext' => ".view"
            ],
        ];

        if(self::$DEFAULT_ROUTE_SET === false && self::$USE_DEFAULT_ROUTE) {
            self::$DEFAULT_ROUTE_SET = true;

            self::$INC_CUSTOM_ROUTE = array_merge(
                self::$INC_CUSTOM_ROUTE,
                $default_routes('back'),
                $default_routes('front')
            );
        }

        foreach (self::$INC_CUSTOM_ROUTE as $k => $v){
            if($type != $k) continue;

            $using_custom_route = true;
            $type_loc = $v['root'];
            $type = $v['ext'] ?? ".inc";
            break;
        }

        if(!$using_custom_route)
            switch ($type) {
                default:
                    $type_loc = $inc_root;
                    $type = ".inc";
                    break;
                case "view":
                    $type_loc = $view_root;
                    $type = ".view";
                    break;
            }

        $file = $type_loc . $file . $type;
        $var = array_replace_recursive($vars, array_replace_recursive(self::get_inc_vars(), $vars));
        $obj = LayObject::instance();

        $meta = $var['META'] ?? [];
        $local = $var['LOCAL'] ?? [];
        $local_array = $var['LOCAL_ARRAY'] ?? [];

        $meta = $obj->to_object($meta);
        $local = $obj->to_object($local);

        if(!file_exists($file) && $strict)
            Exception::throw_exception("execution Failed trying to include file ($file)","FileNotFound");

        if(isset($vars['INCLUDE_AS_STRING']) && $vars['INCLUDE_AS_STRING'])
            return $this->inc_file_as_string($file, $meta, $local, $local_array);

        $layConfig = $this;

        $once ? include_once $file : include $file;
        return null;
    }

}
