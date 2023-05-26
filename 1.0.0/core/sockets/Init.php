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

    private static bool $INITIALIZED = false;
    private static function is_init() : void {
        if(!self::$INITIALIZED)
            self::instance()->init();
    }

    public function init() : self {
        $options = self::$layConfigOptions ?? [];

        $options = array_merge($options,[
            # This tells Lay to use `dev/` folder on production rather than `prod/` folder as the source for client resources
            "use_prod" => $options['switch']['use_prod'] ?? true,
            # on true, this strips white spaces from the html output. Note; it doesn't strip white spaces off the <script></script> elements or anything in-between elements for that matter
            "compress_html" => $options['switch']['compress_html'] ?? true,
            # This forces Lay to use https:// instead of http:// for its proto; Default is true for production environment
            # A use case is; when simulating production server, but don't have access to ssl
            "use_https" => $options['switch']['use_https'] ?? true,
            "default_inc_routes" => $options['switch']['default_inc_routes'] ?? true,
            # This comes in play when adding files with in-house inclusion function, it determines if files should be
            # accessible as <array> or <object>
            "use_objects" => $options['switch']['use_objects'] ?? true,

            "env" => $options['header']['env'] ?? "dev",
            "name"    => [
                "short" => $options['meta']['name']['short'] ?? "Lay - Lite PHP Framework",
                "full" => $options['meta']['name']['full'] ?? "Lay - Lite PHP Framework | Simple, Light, Quick",
            ],
            "author" => $options['meta']['author'] ?? "Lay - Lite PHP Framework",
            "copy" => $options['meta']['copy'] ?? "Copyright &copy; Lay - Lite PHP Framework " . date("Y") . ", All Rights Reserved",
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
        $dir            = AutoLoader::get_root_dir();
        $base           = explode(str_replace("/", $slash, $_SERVER['DOCUMENT_ROOT']), $dir);
        $http_host      = $_SERVER['HTTP_HOST'] ?? "cli";
        $env_host       = $_SERVER['REMOTE_ADDR'] ?? "cli";
        $proto          = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $_SERVER['REQUEST_SCHEME'];
        $proto_plain    = $proto;
        $proto          = $proto . "://";
        $base_no_proto  = rtrim(str_replace($slash,"/", end($base)),"/");
        $localhost      = ["127.0.","192.168."];
        $env            = $options['env'];

        switch (strtolower($env)){
            default: $env = "dev"; break;
            case "prod": case "production": $env = "prod"; break;
        }

        $is_live_server = ($env_host !== "localhost" && strpos($env_host,$localhost[0]) === false && strpos($env_host,$localhost[1]) === false) || $env == "prod";

        if($is_live_server) {
            $env            = "prod";
            $env_src        = $options['use_prod'] ? $env : "dev";
        }

        self::$ENV      = self::$ENV ?? $env;
        self::$client   = new stdClass();
        self::$server   = new stdClass();
        $env_src        = $env_src ?? $env;
        $base           = $proto . $http_host . $base_no_proto . "/";
        $base_no_proto  = $http_host . $base_no_proto;

        // containerize vital attributes inside the options array for internal_site_data
        $options['base'] = $base;
        $options['base_no_proto'] = $base_no_proto;
        $options['base_no_proto_no_www'] = str_replace("www.","", $base_no_proto);
        $options['proto'] = $proto_plain;
        $options['mail'][0] = $options['mail'][0] ?? "info@" . $base_no_proto;

        self::set_internal_res_client($base, $env_src);
        self::set_internal_res_server($dir);
        self::set_internal_site_data($options);

        self::$INITIALIZED = true;
        return self::$instance;
    }
}