<?php
declare(strict_types=1);
namespace Lay\orm;
use mysqli;

trait Config{
    private static mysqli $link;
    private static string $CHARSET = "utf8mb4";
    private static array $DB_ARGS = [
        "host" => null,
        "user" => null,
        "password" => null,
        "db" => null,
        "port" => null,
        "socket" => null,
        "env" => null,
        "silent" => false,
        "ssl" => [
            "key" => null,
            "certificate" => null,
            "ca_certificate" => null,
            "ca_path" => null,
            "cipher_algos" => null,
            "flag" => 0
        ],
    ];

    private static function _init(mysqli|array|null $connection) : void {
        $me = self::instance();
        $http_host = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_HOST'];
        $localhost = ["127.0.","192.168."];
        $env = is_array($connection) ? @$connection['env'] : null;

        // confirm development environment or guess it based on host
        (empty($env) && ($http_host == "localhost" || str_contains($http_host, $localhost[0]) || str_contains($http_host, $localhost[1]))) ?
            $me->set_env("DEVELOPMENT") :
            $me->set_env("PRODUCTION");

        $me->set_db($connection);
    }

    /**
     * Connect Controller Manually From Here
     * @return mysqli|null
     **/
    private function connect() : ?mysqli {
        extract(self::$DB_ARGS);
        $charset = $charset ?? self::$CHARSET;
        $this->set_env($env ?? $this->get_env());
        $cxn = $this->ping(true,null, true);
        $port = $port ?? null;
        $socket = $socket ?? null;

        if(!($cxn['host'] == $host and $cxn['user'] == $user and $cxn['db'] == $db)) {
            $mysqli = null;

            try {
                if(!empty(@$ssl['certificate']) || !empty(@$ssl['ca_certificate'])){
                    $mysqli = mysqli_init();
                    mysqli_ssl_set(
                        $mysqli,
                        @$ssl['key'],
                        @$ssl['certificate'],
                        @$ssl['ca_certificate'],
                        @$ssl['ca_path'],
                        @$ssl['cipher_algos'],
                    );
                    mysqli_real_connect($mysqli, $host, $user, $password, $db, $port, $socket, (int) @$ssl['flag']);
                }

                if (!$mysqli){
                    $mysqli = mysqli_connect($host, $user, $password, $db, $port, $socket);
                    $mysqli->set_charset($charset);
                }


                $this->set_link($mysqli);
            }catch (\Exception $e){}

            if(!$mysqli){
                if (filter_var($silent,FILTER_VALIDATE_BOOL))
                    return null;
                else
                    $this->show_exception(2);
            }
        }

        return $this->get_link();
    }

    /**
     * Connect Controller Using Existing Link
     * @param mysqli $link
     * @return mysqli
     */
    private function plug(mysqli $link) : mysqli {
        $cxn_old = $this->ping(true);

        if(empty($cxn_old['host']) || empty($cxn_old['user']) || empty($cxn_old['db']))
            $this->set_link($link);
        else {
            $cxn_new = $this->ping(true, $link);
            if (!($cxn_old['host'] == $cxn_new['host'] and $cxn_old['user'] == $cxn_new['user'] and $cxn_old['db'] == $cxn_new['db']))
                $this->set_link($link);
        }
        return $this->get_link();
    }

    /**
     * Check Database Connection
     * @param bool $ignore_msg false by default to echo connection info
     * @param mysqli|null $link link to database connection
     * @param bool $ignore_no_conn false by default to silence no connection error
     * @return array containing [host,user,db]
     **/
    public function ping(bool $ignore_msg = false, ?mysqli $link = null, bool $ignore_no_conn = false) : array {
        $cxn = $link ?? $this->get_link() ?? null; $db = ""; $usr = ""; $host = "";
        if($cxn){
            if(isset($this->get_link()->host_info)) {
                if (@mysqli_ping($cxn)) {
                    $x = $this->query("SELECT SUBSTRING_INDEX(host, ':', 1) AS host_short,
                    USER AS users, db FROM information_schema.processlist", ["fetch_as" => "assoc", "query_type" => "select"]);
                    $db = $x['db'];
                    $usr = $x['users'];
                    $host = $x['host_short'];
                    if (!$ignore_msg) $this->show_exception(1, [$db, $usr, $host]);
                }
                else if (!$ignore_no_conn) $this->show_exception(0);
            }
        } return ["host" => $host, "user" => $usr, "db" => $db];
    }

    public function close(?mysqli $link = null, bool $silent_error = false) : bool {
        try {
            return mysqli_close($link ?? $this->get_link());
        }catch (\Exception $e){
            if(!$silent_error)
                $this->show_exception(3);
        }

        return false;
    }
    public function set_db(mysqli|array $args) : void {
        if(!is_array($args)) {
            $this->plug($args);
            return;
        }

        self::$DB_ARGS = $args;
        $this->connect();
    }
    public function get_db_args() : array { return self::$DB_ARGS; }
    public function set_link(mysqli $link): void { self::$link = $link;}

    public function get_link(): ?mysqli { return self::$link ?? null; }
}