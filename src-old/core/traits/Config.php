<?php
declare(strict_types=1);

namespace Lay\core\traits;

use Lay\core\Exception;
use Lay\libs\LayMail;
use Lay\orm\SQL;

if (!defined("SAFE_TO_INIT_LAY") || !SAFE_TO_INIT_LAY)
    \Lay\core\Exception::throw_exception("This script cannot be accessed this way, please return home", "BadRequest");

trait Config
{
    private static SQL $SQL_INSTANCE;
    private static array $CONNECTION_ARRAY;
    private static array $SMTP_ARRAY;
    private static array $layConfigOptions;
    private static bool $DEFAULT_ROUTE_SET = false;
    private static bool $USE_DEFAULT_ROUTE = true;
    private static bool $COMPRESS_HTML;

    private function switch(string $key, mixed $value): self {
        self::$layConfigOptions['switch'][$key] = $value;
        return self::$instance;
    }

    private function metadata(string $key, mixed $value) : self {
        self::$layConfigOptions['meta'][$key] = $value;
        return self::$instance;
    }

    private function header_data(string $key, mixed $value) : self {
        self::$layConfigOptions['header'][$key] = $value;
        return self::$instance;
    }

    public function dont_compress_html() : self {
        return $this->switch("compress_html", false);
    }

    public function dont_use_prod_folder() : self {
        return $this->switch("use_prod", false);
    }

    public function dont_use_https() : self {
        return $this->switch("use_https", false);
    }

    public function dont_use_default_inc_routes() : self {
        return $this->switch("default_inc_routes", false);
    }

    public function dont_use_objects() : self {
        return $this->switch("use_objects", false);
    }

    /**
     * Prevents the data sent through the ViewHandler of a specific domain from being cached.
     * This only takes effect in development environment, if Lay detects the server is in production, it'll cache by default
     * @return Config|\Lay\core\LayConfig
     */
    public function dont_cache_domains() : self {
        return $this->switch("cache_domains", false);
    }

    public function dont_cache_views() : self {
        return $this->switch("cache_views", false);
    }

    public function set_env(string $env = "dev"): self {
        return $this->header_data("env", $env);
    }

    public function init_name(string $short, string $full) : self {
        return $this->metadata("name", [ "short" => $short,  "full" => $full ]);
    }

    public function init_color(string $pry, string $sec) : self {
        return $this->metadata("color", [ "pry" => $pry,  "sec" => $sec ]);
    }
    public function init_mail(string ...$emails) : self {
        return $this->metadata("mail", $emails);
    }
    public function init_tel(string ...$tel) : self {
        return $this->metadata("tel", $tel);
    }
    public function init_author(string $author) : self {
        return $this->metadata("author", $author);
    }
    public function init_copyright(string $copyright) : self {
        return $this->metadata("copy", $copyright);
    }

    public function init_end() : void {
        self::initialize();
    }

    public function init_others(array $other_data): self
    {
        self::$layConfigOptions['others'] = $other_data;
        return self::$instance;
    }

    public static function session_start(array $flags = []): void {
        if (isset($_SESSION))
            return;

        $cookie_opt = [];
        $flags['expose_php'] ??= false;
        $flags['timezone'] ??= 'Africa/Lagos';

        date_default_timezone_set($flags['timezone']);

        if(!$flags['expose_php'])
            header_remove('X-Powered-By');

        if (isset($flags['only_cookies']))
            ini_set("session.use_only_cookies", ((int)$flags['only_cookies']) . "");

        if (self::$ENV_IS_PROD && isset($flags['http_only']) ?? isset($flags['httponly']))
            $cookie_opt['httponly'] = filter_var($flags['httponly'] ?? $flags['http_only'], FILTER_VALIDATE_BOOL);

        if (self::$ENV_IS_PROD && isset($flags['secure']))
            $cookie_opt['secure'] = filter_var($flags['secure'], FILTER_VALIDATE_BOOL);

        if (self::$ENV_IS_PROD && isset($flags['samesite']))
            $cookie_opt['samesite'] = ucfirst($flags['samesite']);

        if(!empty($cookie_opt))
            session_set_cookie_params($cookie_opt);

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

    public function init_orm(bool $connect_by_default = true): self {
        if(isset(self::$CONNECTION_ARRAY)) {
            if($connect_by_default)
                self::connect(self::$CONNECTION_ARRAY);

            return $this;
        }

        $file = self::get_env() == "DEV" ? 'dev' : 'prod';

        if(!file_exists(self::instance()->get_res__server('db') . $file . ".lenv")) {
            Exception::throw_exception("db file does not exist", "NoDbEnvFile");
            return $this;
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

        if($connect_by_default)
            self::connect($map);

        return $this;
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

        if(!isset($orm->query_info))
            return;

        $orm?->close($orm->get_link() ?? $link, true);
    }

    public static function include_sql(bool $include = true, array $connection_param = []): ?SQL
    {
        self::is_init();
        self::$CONNECTION_ARRAY = $connection_param;
        return $include ? self::connect($connection_param) : null;
    }
}
