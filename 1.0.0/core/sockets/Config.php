<?php
declare(strict_types=1);
namespace Lay\core\sockets;
use Lay\core\Exception;
use Lay\libs\ObjectHandler;
use Lay\orm\SQL;
use Lay\AutoLoader;
use stdClass;

trait Config{
    private static SQL $SQL_INSTANCE;
    private static array $CONNECTION_ARRAY;
    private static array $layConfigOptions;
    private static string $ENV;
    private static object $client;
    private static object $server;
    private static object $site;
    private static bool $DEFAULT_ROUTE_SET = false;
    private static bool $USE_DEFAULT_ROUTE = true;
    private static bool $USE_OBJS;
    private static bool $COMPRESS_HTML;

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
}