<?php
declare(strict_types=1);

namespace Lay\core\sockets;

use Lay\core\Exception;
use Lay\libs\ObjectHandler;
use Lay\orm\SQL;
use Lay\AutoLoader;
use stdClass;

trait Config
{
    private static SQL $SQL_INSTANCE;
    private static array $CONNECTION_ARRAY;
    private static array $layConfigOptions;
    private static bool $DEFAULT_ROUTE_SET = false;
    private static bool $USE_DEFAULT_ROUTE = true;
    private static bool $USE_OBJS;
    private static bool $COMPRESS_HTML;

    public static function session_start(array $flags = []): void
    {
        if (isset($_SESSION))
            return;

        if (isset($flags['http_only']))
            ini_set("session.cookie_httponly", ((int)$flags['http_only']) . "");

        if (isset($flags['only_cookies']))
            ini_set("session.use_only_cookies", ((int)$flags['only_cookies']) . "");

        if (isset($flags['secure']))
            ini_set("session.cookie_secure", ((int)$flags['secure']) . "");

        session_start();
    }

    /**
     * @param array $allowed_origins String[] of allowed origins like "http://example.com"
     * @param bool $allow_all
     * @param \Closure|null $other_headers example function(){ header("Access-Control-Allow-Origin: Origin, X-Requested-With, Content-Type, Accept"); }
     * @return bool
     */
    public static function set_cors(array $allowed_origins, bool $allow_all = false, ?\Closure $other_headers = null): bool
    {
        $http_origin = rtrim($_SERVER['HTTP_ORIGIN'] ?? $_SERVER['HTTP_REFERER'] ?? "", "/");

        if ($allow_all) {
            $http_origin = "*";
        } else {
            if (!in_array($http_origin, $allowed_origins, true))
                return false;
        }

        // in an ideal word, this variable will only be empty if it's the same origin
        if (empty($http_origin))
            return true;

        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Origin: $http_origin");
        header('Access-Control-Max-Age: 86400');    // cache for 1 day

        // Access-Control headers are received during OPTIONS requests
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
                header("Access-Control-Allow-Methods: GET, POST, OPTIONS");

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
                header("Access-Control-Allow-Headers:{$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

            exit(0);
        }

        if ($other_headers !== null)
            $other_headers("");

        return true;

    }

    public function is_mobile(): bool
    {
        return (bool)strpos(strtolower($_SERVER['HTTP_USER_AGENT'] ?? "cli"), "mobile");
    }

    public function switch(array $bool_valued_array): self
    {
        self::$layConfigOptions['switch'] = $bool_valued_array;
        return self::$instance;
    }

    public function header(array $project_wide_config): self
    {
        self::$layConfigOptions['header'] = $project_wide_config;
        return self::$instance;
    }

    public function meta(array $project_meta_data): self
    {
        self::$layConfigOptions['meta'] = $project_meta_data;
        return self::$instance;
    }

    public function others(array $project_other_meta_data): self
    {
        self::$layConfigOptions['others'] = $project_other_meta_data;
        return self::$instance;
    }

    public static function get_env(): string
    {
        self::is_init();
        return strtoupper(self::$ENV);
    }

    public static function get_orm(): SQL
    {
        self::is_init();
        return self::$SQL_INSTANCE;
    }

    public static function is_page_compressed(): bool
    {
        self::is_init();
        return self::$COMPRESS_HTML;
    }

    public static function connect(?array $connection_params = null): SQL
    {
        self::is_init();
        $env = self::$ENV;

        if (isset($connection_params['host']) || isset(self::$CONNECTION_ARRAY['host'])) {
            $opt = self::$CONNECTION_ARRAY ?? $connection_params;
        } else {
            $opt = self::$CONNECTION_ARRAY[$env] ?? $connection_params[$env];
        }

        if (empty($opt))
            Exception::throw_exception("Invalid Connection Parameter Passed");

        if (is_array($opt))
            $opt['env'] = $opt['env'] ?? $env;

        if ($env == "prod")
            $opt['env'] = "prod";

        self::$SQL_INSTANCE = SQL::init($opt);
        return self::$SQL_INSTANCE;
    }

    public static function close_sql(?\mysqli $link = null): void
    {
        self::is_init();
        if (!isset(self::$SQL_INSTANCE))
            return;

        $orm = self::$SQL_INSTANCE;
        if ($orm) $orm->close($orm->get_link() ?? $link, true);
    }

    public static function include_sql(bool $include = true, array $connection_param = []): ?SQL
    {
        self::is_init();
        self::$CONNECTION_ARRAY = $connection_param;
        return $include ? self::connect($connection_param) : null;
    }
}