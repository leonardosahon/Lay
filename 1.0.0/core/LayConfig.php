<?php
declare(strict_types=1);
namespace Lay\core;
use Lay\libs\ObjectHandler;
use Lay\orm\SQL;
use Lay\AutoLoader;
use stdClass;
if(!@$EXPOSE_PHP)
    header_remove('X-Powered-By');
/**
 * Core Config File
 */
final class LayConfig{
    private static LayConfig $instance;
    private static SQL $SQL_INSTANCE;
    private static array $CONNECTION_ARRAY;
    private static array $layConfigOptions;
    private static string $ENV;
    private static object $client;
    private static object $server;
    private static object $site;
    private static array $INC_VARS = ["META" => null, "PAINT" => null];
    private static array $INC_CUSTOM_ROUTE = [];
    private static bool $DEFAULT_ROUTE_SET = false;
    private static bool $USE_DEFAULT_ROUTE = true;
    private static bool $USE_OBJS;
    private static bool $COMPRESS_HTML;
    private static bool $INITIALIZED = false;

    private function __construct(){}
    private function __clone(){}
    private static function is_init() : void {
        if(!self::$INITIALIZED)
            Exception::throw_exception(
                "Lay has not been initialized properly, ensure to use the `->init()` function after getting `LayConfig::instance()`.<br><br>
                    <u>Example</u>
                    LayConfig::instance()<br>->project('CURRENT_PROJECT_FOLDER_NAME')<br>->init();<br>
                    // If nothing is passed to the `->project()` function, Lay will assume the project resides at the root folder 
                    ",
                "Wrong-Init-Procedure");
    }

    public static function instance() : self{
        if (!isset(self::$instance)) {
            self::$instance = new LayConfig();
        }
        return self::$instance;
    }
    ////////////////////// Options
    public function switch(array $bool_valued_array) : self {
        self::$layConfigOptions['switch'] = $bool_valued_array;
        return self::$instance;
    }
    public function header(array $project_wide_config) : self {
        self::$layConfigOptions['header'] = $project_wide_config;
        return self::$instance;
    }
    public function meta(array $project_meta_data) : self {
        self::$layConfigOptions['meta'] = $project_meta_data;
        return self::$instance;
    }
    public function others(array $project_other_meta_data) : self {
        self::$layConfigOptions['others'] = $project_other_meta_data;
        return self::$instance;
    }
    /////////// INITIALIZE
    public function init() : self {
        $options = self::$layConfigOptions ?? [];

        $options = array_merge($options,[
            # This tells config to use `dev/` folder on production server
            # instead of `prod/` folder as the source for client resources
            "use_prod" => $options['switch']['use_prod'] ?? true,
            # as the name implies, this enables/disables html output compression
            "compress_html" => $options['switch']['compress_html'] ?? true,
            # This forces Lay to use https:// instead of http:// for its proto; Default is true for production environment
            # A use case can be when simulating production server, but don't have access to ssl
            "use_https" => $options['switch']['use_https'] ?? true,
            "default_inc_routes" => $options['switch']['default_inc_routes'] ?? true,
            # This comes in play when adding files with in-house inclusion function, it determines if files should be
            # accessible as <array> or <object>
            "use_objects" => $options['switch']['use_objects'] ?? true,
            # If the project has intentions of using subdomain, tell Lay to automatically capture the new subdomain when accessed
            "has_subdomain" => $options['switch']['has_subdomain'] ?? false,
            # This takes the link to the heroku project, if the project is deployed through the platform
            # This can work with any service like heroku, it's simply passing the domain name on that platform
            "heroku" => $options['header']['heroku'] ?? "",
            "domain" => $options['header']['domain'] ?? null,
            "env" => $options['header']['env'] ?? "dev",
            "name"    => [
                "short" => $options['meta']['name']['short'] ?? "Lay Sample Project",
                "full" => $options['meta']['name']['full'] ?? "Lay Sample Project | Simple, Light, Quick",
            ],
            "author" => $options['meta']['author'] ?? "Osai Technologies",
            "copy" => $options['meta']['copy'] ?? "Copyright &copy; Osai Technologies " . date("Y") . ", All Rights Reserved",
            "color" => [
                "pry" => $options['meta']['color']['pry'] ?? "",
                "sec" => $options['meta']['color']['sec'] ?? "",
            ],
            "mail" => $options['meta']['mail'] ?? [],
            "tel" => $options['meta']['tel'] ?? [],
            "others" => $options['others'] ?? []
        ]);

        self::$USE_OBJS = $options['use_objects'];
        self::$COMPRESS_HTML = $options['compress_html'];
        self::$USE_DEFAULT_ROUTE = $options['default_inc_routes'];
        $slash          = DIRECTORY_SEPARATOR;
        $obj_handler    = ObjectHandler::instance();
        $dir            = AutoLoader::get_root_dir();
        $base           = explode(str_replace("/",$slash,$_SERVER['DOCUMENT_ROOT']),$dir);
        $http_host      = $_SERVER['HTTP_HOST'] ?? "cli";
        $env_host       = $_SERVER['REMOTE_ADDR'] ?? "cli";
        $proto          = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $_SERVER['REQUEST_SCHEME'];
        $proto          = $proto . "://";
        $base_no_proto  = rtrim(str_replace($slash,"/",end($base)),"/");
        $localhost      = ["127.0.","192.168."];
        $env            = $options['env'];
        switch (strtolower($env)){
            default: $env = "dev"; break;
            case "prod": case "production": $env = "prod"; break;
            case "heroku": $env = "heroku"; break;
        }

        $is_live_server = ($env_host !== "localhost" && strpos($env_host,$localhost[0]) === false && strpos($env_host,$localhost[1]) === false) || $env == "prod";
        $is_local_server = $env_host === "localhost" || strpos($env_host,$localhost[0]) !== false || strpos($env_host,$localhost[1]) !== false;

        if($is_live_server){
            $env            = "prod";
            $env_src        = $options['use_prod'] ? $env : "dev";
        }

        if(!$is_local_server && $http_host == $options['heroku'])
            self::$ENV      = "heroku";

        self::$ENV      = self::$ENV ?? $env;
        self::$client   = new stdClass();
        self::$server   = new stdClass();
        $env_src        = $env_src ?? $env;
        $base           = $proto . $http_host . $base_no_proto . "/";
        $client         = "res/client/";
        $server         = "res" . $slash . "server" . $slash;
        $root_client    = $base . $client;
        $root_server    = $dir  . $server;
        $front          = $env_src . "/front/";
        $back           = $env_src . "/back/";
        $custom         = $env_src . "/custom/";

        self::$client = $obj_handler->to_object([
            "api"    =>     $base . "api/",
            "lay"    =>     $base . "Lay/",
            "upload" =>     $base . "res/uploads/",
            "custom"  => [
                "root"      =>     $root_client . $custom,
                "img"       =>     $root_client . $custom . "images/",
                "css"       =>     $root_client . $custom . "css/",
                "js"        =>     $root_client . $custom . "js/",
                "plugin"    =>     $root_client . $custom . "plugin/",
            ],
            "front"   =>   [
                "root"      =>     $root_client . $front,
                "img"       =>     $root_client . $front . "images/",
                "css"       =>     $root_client . $front . "css/",
                "js"        =>     $root_client . $front . "js/",
            ],
            "back"   =>   [
                "root"  =>         $root_client . $back,
                "img"   =>         $root_client . $back . "images/",
                "css"   =>         $root_client . $back . "css/",
                "js"    =>         $root_client . $back . "js/",
            ],
        ]);

        self::$server = $obj_handler->to_object([
            "dir"     =>   $dir,
            "inc"     =>   $root_server     . "includes"    . $slash,
            "ctrl"    =>   $root_server     . "controller"  . $slash,
            "view"    =>   $root_server     . "view"        . $slash,
            "upload"  =>   "res"            . $slash . "uploads" . $slash,
        ]);

        self::$site = $obj_handler->to_object([
            "base" => $base,
            "base_no_proto" => $base_no_proto,
            "author"  => $options['author'],
            "copy" => $options['copy'],
            "name" => $options['name'],
            "img"  => [
                "logo" => self::$client->custom->img . "logo.png",
                "favicon" => self::$client->custom->img . "favicon.png",
                "icon" => self::$client->custom->img . "icon.png",
            ],
            "color" => $options['color'],
            "mail" => [
                $options['mail'][0] ?? ("info@" . $base_no_proto),
                $options['mail'][1] ?? ("support@" . $base_no_proto)
            ],
            "tel" => $options['tel'],
            "others" => $options['others']
        ]);

        self::$INITIALIZED = true;
        return self::$instance;
    }

    ///### Development Environment
    public static function get_env() : string {
        self::is_init();
        return strtoupper(self::$ENV);
    }
    public static function get_orm() : SQL {
        self::is_init();
        return self::$SQL_INSTANCE;
    }
    public static function is_page_compressed() : bool {
        self::is_init();
        return self::$COMPRESS_HTML;
    }

    ///### Database Connection
    public static function connect(?array $connection_params = null): SQL {
        self::is_init();
        $env = self::$ENV;
        $opt = self::$CONNECTION_ARRAY[$env] ?? $connection_params[$env];

        if(empty($opt))
            Exception::throw_exception("Invalid Connection Parameter Passed");

        if(is_array($opt))
            $opt['env'] = $opt['env'] ?? $env;

        if($env == "prod")
            $opt['env'] = "prod";

        self::$SQL_INSTANCE = SQL::init($opt);
        return self::$SQL_INSTANCE;
    }
    public static function close_sql(?\mysqli $link = null) : void {
        self::is_init();
        if(!isset(self::$SQL_INSTANCE))
            return;

        $orm = self::$SQL_INSTANCE;
        if($orm) $orm->close($orm->get_link() ?? $link);
    }
    public static function include_sql(bool $include = true, array $connection_param = []) : ?SQL {
        self::is_init();
        self::$CONNECTION_ARRAY = $connection_param;
        return $include ? self::connect($connection_param) : null;
    }

    ///### Assets Resource and Page Metadata
    private static function get_res($resource, string ...$index_chain) {
        foreach ($index_chain as $v){
            $resource = $resource->{$v};
        }
        return $resource;
    }

    /**
     * @param object $resource
     * @param string $index
     * @param array $accepted_index
     * @return void
     */
    private static function set_res(object &$resource, array $accepted_index = [], ...$index) : void {
        if(!empty($accepted_index) && !in_array($index[0],$accepted_index,true))
            Exception::throw_exception("The index [$index[0] being accessed may not exist or is forbidden.
                You can only access these index: " . implode(",",$accepted_index),"Invalid Index");

        $value = end($index);
        array_pop($index);

        $object_push = function (&$key) use ($value) {
            $key = $value;
        };
        switch (count($index)){
            default:
                $object_push($resource->{$index[0]});
                break;
            case 2:
                $object_push($resource->{$index[0]}->{$index[1]});
                break;
            case 3:
                $object_push($resource->{$index[0]}->{$index[1]}->{$index[2]});
                break;
            case 4:
                $object_push($resource->{$index[0]}->{$index[1]}->{$index[2]}->{$index[3]});
                break;
            case 5:
                $object_push($resource->{$index[0]}->{$index[1]}->{$index[2]}->{$index[3]}->{$index[4]});
                break;
        }
    }

    # Client Side
    public static function set_res__client(...$index__and__value) : void {
        self::is_init();
        self::set_res(self::$client,["back","front",],...$index__and__value);
    }
    public function get_res__client(string ...$index_chain) {
        self::is_init();
        return self::get_res(self::$client,...$index_chain);
    }

    # Server Side
    public static function set_res__server(...$index__and__value) : void {
        self::is_init();
        self::set_res(self::$server,["view","ctrl","inc","upload",],...$index__and__value);
    }
    public function get_res__server(string ...$index_chain) {
        self::is_init();
        return self::get_res(self::$server,...$index_chain);
    }

    # Site Metadata
    public static function set_site_data(...$index__and__value) : void {
        self::is_init();
        self::set_res(self::$site,[],...$index__and__value);
    }
    public function get_site_data(string ...$index_chain) {
        self::is_init();
        return self::get_res(self::$site,...$index_chain);
    }

    ///### Include Files
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

        $layConfig = $this;

        if(!file_exists($file) && $strict)
            Exception::throw_exception("execution Failed trying to include file ($file)","File-Not-Found");

        if(isset($vars['INCLUDE_AS_STRING']) && $vars['INCLUDE_AS_STRING'])
            return $this->inc_file_as_string($file,$meta,$local,$local_raw);

        $once ? include_once $file : include $file;
        return null;
    }

    ///### View

    /**
     * @param array $page_data
     * @param ...$options
     * @see ViewPainter
     */
    public function view(array $page_data, ...$options) : void {
        ViewPainter::instance()->paint($page_data,...$options);
    }
    public function view_const(array $page_data) : void {
        ViewPainter::constants($page_data);
    }
    public function inject_view(string $root = "/", string $get_name = "brick") : string {
        $handle_assets_like_js = function ($view){
            $ext_array = ["js","css","map","jpeg","jpg","png","gif","jiff","svg"];
            $x = explode(".",$view);
            $ext = strtolower(end($x));

            if(count($x) > 1 && in_array($ext,$ext_array,true))
                http_response_code(404);

            return $view;
        };

        $project_root = self::get_site_data('base_no_proto');
        $view = $_GET[$get_name] ?? "";
        $view = str_replace($project_root,"",$view);

        if($root != "/") $view = str_replace(["/$root/","/$root","$root/"],"", $view);

        $view = ltrim($view,"/");
        return $handle_assets_like_js($view);
    }
}