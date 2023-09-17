<?php
declare(strict_types=1);

namespace Lay\core\sockets;

use Lay\core\Exception;
use Lay\libs\LayMail;
use Lay\libs\LayObject;
use Lay\orm\SQL;
use Lay\AutoLoader;
use stdClass;

trait Config
{
    private static SQL $SQL_INSTANCE;
    private static array $CONNECTION_ARRAY;
    private static array $SMTP_ARRAY;
    private static array $layConfigOptions;
    private static bool $DEFAULT_ROUTE_SET = false;
    private static bool $USE_DEFAULT_ROUTE = true;
    private static bool $USE_OBJS;
    private static bool $COMPRESS_HTML;

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

    public static function get_ip(): string {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        foreach (
            [
                'HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_X_CLUSTER_CLIENT_IP',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
                'REMOTE_ADDR'
            ] as $key
        ) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip_address) {
                    $ip_address = trim($ip_address);

                    if (filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false)
                        return $ip_address;
                }
            }

        }

        return $ip_address ?? "";
    }

    public static function get_env(): string
    {
        self::is_init();
        return strtoupper(self::$ENV);
    }

    public static function set_smtp(): void {
        self::is_init();

        if(isset(self::$SMTP_ARRAY))
            return;

        if(!file_exists(self::instance()->get_res__server('lay_env') . "smtp.lenv")) {
            Exception::throw_exception("smtp file does not exist", "NoSmtpEnvFile");
            return;
        }

        $map = LayMail::get_credentials();

        $output = self::instance()->inc_file_as_string(self::instance()->get_res__server('lay_env') . "smtp.lenv");

        foreach (explode("\n",$output) as $e){
            if(empty($e)) continue;
            $entry = explode("=",$e);
            $key = strtolower($entry[0]);
            $value = $entry[1];

            if(!empty($value) && $x = filter_var($value, FILTER_VALIDATE_INT)) {
                $value = $x;
            }

            if(!is_int($value) && str_starts_with($value,'$L{')){
                $value = explode("->",str_replace(['$L{','}'],"", $value));
                $value = self::instance()->get_site_data(...$value);
            }
            $map[$key] = $value;
        }

        self::$SMTP_ARRAY = $map;

        LayMail::set_credentials($map);
    }

    public static function set_orm(bool $include = true): ?SQL {
        self::is_init();

        if(isset(self::$CONNECTION_ARRAY))
            return $include ? self::connect(self::$CONNECTION_ARRAY) : null;

        $file = self::get_env() == "DEV" ? 'dev' : 'prod';

        if(!file_exists(self::instance()->get_res__server('db') . $file . ".lenv")) {
            Exception::throw_exception("db file does not exist", "NoDbEnvFile");
            return null;
        }

        $output = self::instance()->inc_file_as_string(self::instance()->get_res__server('db') . $file . ".lenv");

        $map = SQL::instance()->get_db_args();

        foreach (explode("\n",$output) as $e){
            if(empty($e)) continue;
            $entry = explode("=",$e);
            $key = strtolower(str_replace("DB_","",$entry[0]));
            $value = $entry[1];

            if(!empty($value)) {
                if (filter_var($value, FILTER_VALIDATE_INT))
                    $value = filter_var($value, FILTER_VALIDATE_INT);

                if (filter_var($value, FILTER_VALIDATE_BOOL) && !is_int($value))
                    $value = filter_var($value, FILTER_VALIDATE_BOOL);
            }


            if(str_contains($key,"ssl")){
                $key = str_replace("ssl_","",$key);
                $map['ssl'][$key] = $value;
            }
            else {
                if($key == "name")
                    $key = "db";

                $map[$key] = $value;
            }
        }

        self::$CONNECTION_ARRAY = $map;

        return $include ? self::connect($map) : null;
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
        $orm?->close($orm->get_link() ?? $link, true);
    }

    public static function include_sql(bool $include = true, array $connection_param = []): ?SQL
    {
        self::is_init();
        self::$CONNECTION_ARRAY = $connection_param;
        return $include ? self::connect($connection_param) : null;
    }
}