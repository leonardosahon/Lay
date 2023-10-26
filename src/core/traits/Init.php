<?php
declare(strict_types=1);
namespace Lay\core\traits;
use Lay\core\Exception;
use Lay\libs\LayObject;
use Lay\orm\SQL;
use Lay\AutoLoader;
use stdClass;

if(isset($DONT_EXPOSE_PHP))
    header_remove('X-Powered-By');
if(!isset($DISABLE_TIMEZONE))
    date_default_timezone_set('Africa/Lagos');

trait Init {
    private static string $ENV;
    private static string $dir;
    private static string $base;
    private static string $base_no_proto;
    private static string $base_no_proto_no_www;
    private static string $env_host;
    private static string $proto_plain;

    private static bool $INITIALIZED = false;
    private static bool $FIRST_CLASS_CITI_ACTIVE = false;
    public static bool $ENV_IS_PROD = false;
    public static bool $ENV_IS_DEV = true;

    private static function init_first_class() : void {
        if(!self::$FIRST_CLASS_CITI_ACTIVE)
            self::first_class_citizens();
    }

    private static function set_dir() : void {
        self::$dir = AutoLoader::get_root_dir();
    }

    private static function first_class_citizens() : void {
        self::$FIRST_CLASS_CITI_ACTIVE = true;
        self::set_dir();

        $slash          = DIRECTORY_SEPARATOR;
        $base           = explode(str_replace("/", $slash, $_SERVER['DOCUMENT_ROOT']), self::$dir);
        $http_host      = $_SERVER['HTTP_HOST'] ?? "cli";
        $env_host       = $_SERVER['REMOTE_ADDR'] ?? "cli";
        self::$proto_plain = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? $_SERVER['REQUEST_SCHEME'];
        $proto = self::$proto_plain . "://";
        $base_no_proto  = rtrim(str_replace($slash,"/", end($base)),"/");

        self::$base = $proto . $http_host . $base_no_proto . "/";
        self::$base_no_proto  = $http_host . $base_no_proto;
        self::$base_no_proto_no_www  = str_replace("www.","", $base_no_proto);

        $localhost = ["127.0.","192.168.","::1"];

        self::$ENV_IS_PROD = (
            $env_host !== "localhost" &&
            (
                !str_contains($env_host, $localhost[0]) && !str_contains($env_host, $localhost[1]) && !str_contains($env_host, $localhost[2])
            )
        );

        self::$ENV_IS_DEV = !self::$ENV_IS_PROD;

        $options['base'] = self::$base;
        $options['base_no_proto'] = self::$base_no_proto;
        $options['base_no_proto_no_www'] = self::$base_no_proto_no_www;
        $options['proto'] = self::$proto_plain;


        self::set_internal_site_data($options);
    }

    private static function initialize() : self {
        self::init_first_class();

        $options = self::$layConfigOptions ?? [];

        $options = array_merge($options, [
            # This tells Lay to use `dev/` folder on production rather than `prod/` folder as the source for client resources
            "use_prod" => $options['switch']['use_prod'] ?? true,
            # On true, this strips space from the html output. Note; it doesn't strip space off the <script></script> elements or anything in-between elements for that matter
            "compress_html" => $options['switch']['compress_html'] ?? true,
            # This forces Lay to use https:// instead of http:// for its proto; Default is true for production environment
            # A use case is; when simulating production server, but don't have access to ssl
            "use_https" => $options['switch']['use_https'] ?? true,
            "default_inc_routes" => $options['switch']['default_inc_routes'] ?? true,
            # This comes in play, when adding files with in-house inclusion function, it determines if files should be
            # accessible as <array> or <object>
            "use_objects" => $options['switch']['use_objects'] ?? true,
            # Used by the Domain module to instruct the handler to cache all the listed domains in a session or cookie,
            # depending on the value sent by dev
            "cache_domains" => $options['switch']['cache_domains'] ?? true,
            # Used by the View module to instruct the handler to cache all the listed views in a session or cookie,
            # depending on the value sent by dev
            "cache_views" => $options['switch']['cache_views'] ?? true,
            "env" => $options['header']['env'] ?? null,
            "name" => [
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

        $env = $options['env'];

        if($env === null)
            $env = self::$ENV_IS_PROD ? 'prod' : 'dev';

        switch (strtolower($env)) {
            default:
                $env = "dev";
                self::$ENV_IS_PROD = false;
                self::$ENV_IS_DEV = true;
            break;
            case "prod": case "production":
            $env = "prod";
            self::$ENV_IS_PROD = true;
            self::$ENV_IS_DEV = false;
            break;
        }

        self::$client   = new stdClass();
        self::$server   = new stdClass();
        self::$ENV = self::$ENV ?? $env;
        $env_src = $options['use_prod'] ? $env : "dev";
        $options['mail'][0] = $options['mail'][0] ?? "info@" . self::$base_no_proto;

        // Reinitialize first class citizens
        $options['base'] = self::$base;
        $options['base_no_proto'] = self::$base_no_proto;
        $options['base_no_proto_no_www'] = self::$base_no_proto_no_www;
        $options['proto'] = self::$proto_plain;

        self::set_internal_res_client(self::$base, $env_src);
        self::set_internal_site_data($options);
        self::set_internal_res_server(self::$dir);
        self::$INITIALIZED = true;
        return self::$instance;
    }

    public static function is_init(bool $init_first_class = false) : void {
        if($init_first_class && !self::$FIRST_CLASS_CITI_ACTIVE) {
            self::init_first_class();
            return;
        }

        if(!self::$INITIALIZED)
            self::initialize();
    }
}
