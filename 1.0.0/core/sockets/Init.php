<?php
declare(strict_types=1);
namespace Lay\core\sockets;
use Lay\core\Exception;
use Lay\libs\ObjectHandler;
use Lay\orm\SQL;
use Lay\AutoLoader;
use stdClass;

if(isset($DONT_EXPOSE_PHP))
    header_remove('X-Powered-By');
if(!isset($DISABLE_TIMEZONE))
    date_default_timezone_set('Africa/Lagos');

trait Init{
    private static string $ENV;
    private static object $client;
    private static object $server;
    private static object $site;
    private static bool $INITIALIZED = false;
    private static function is_init() : void {
        if(!self::$INITIALIZED)
            self::instance()->init();
    }

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
        $proto_plain    = $proto;
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

        $options['mail'][0] = $options['mail'][0] ?? "info@" . $base_no_proto;

        self::$site = $obj_handler->to_object([
            "base" => $base,
            "base_no_proto" => $base_no_proto,
            "proto" => $proto_plain,
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
                ...$options['mail']
            ],
            "tel" => $options['tel'],
            "others" => $options['others']
        ]);

        self::$INITIALIZED = true;
        return self::$instance;
    }
}