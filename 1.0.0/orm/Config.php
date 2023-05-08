<?php
declare(strict_types=1);
namespace Lay\orm;
use mysqli;

/**
 * Trait Config
 * @package osai\SQL_MODEL
 * @modified 08/11/2021
 */
trait Config{
    private static mysqli $link;
    private static string $CHARSET = "utf8mb4";

    private static function _init($connection) : void {
        $me = self::instance();
        $http_host = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_HOST'];
        $localhost = ["127.0.","192.168."];
        $env = is_array($connection) ? @$connection['env'] : null;

        // confirm development environment or guess it based on host
        (empty($env) && ($http_host == "localhost" || strpos($http_host,$localhost[0]) !== false || strpos($http_host,$localhost[1]) !== false)) ?
            $me->set_env("DEVELOPMENT") :
            $me->set_env("PRODUCTION");

        is_array($connection) ? $me->connect($connection) : $me->plug($connection);
    }

    /**
     * Connect Controller Manually From Here
     * @param $cnn_arg array associative array of connection parameter ["host","user","password","db","env"]
     * env takes either ("dev" || "prod") || ("development" || "production")
     * @return mysqli|null
     **/
    public function connect(array $cnn_arg) : ?mysqli {
        $host = $cnn_arg['host'];
        $usr = $cnn_arg['user'];
        $pass = $cnn_arg['password'];
        $dbname = $cnn_arg['db'];
        $port = $cnn_arg['port'] ?? null;
        $socket = $cnn_arg['socket'] ?? null;
        $charset = $cnn_arg['charset'] ?? self::$CHARSET;
        $this->set_env($cnn_arg['env'] ?? $this->get_env());
        $cxn = $this->ping(true,null, true);
        if(!($cxn['host'] == $host and $cxn['user'] == $usr and $cxn['db'] == $dbname)) {
            $mysqli = null;

            try {
                if(isset($cnn_arg['ssl'])){
                    $mysqli = mysqli_init();
                    mysqli_ssl_set(
                        $mysqli,
                        $cnn_arg['ssl']['key'] ?? "",
                        $cnn_arg['ssl']['certificate'] ?? "",
                        $cnn_arg['ssl']['ca_certificate'] ?? "",
                        $cnn_arg['ssl']['ca_path'] ?? "",
                        $cnn_arg['ssl']['cipher_algos'] ?? "",
                    );
                    mysqli_real_connect($mysqli, $host, $usr, $pass, $dbname, $port, $socket, $cnn_arg['ssl']['flag'] ?? 0);
                }

                if (!$mysqli){
                    $mysqli = mysqli_connect($host, $usr, $pass, $dbname, $port, $socket);
                    $mysqli->set_charset($charset);
                }

                $this->set_link($mysqli);
            }catch (\Exception $e){}

            if(!$mysqli){
                if (isset($cnn_arg['silent']))
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
    public function plug(mysqli $link) : mysqli {
        $cxnOld = $this->ping(true);
        if(empty($cxnOld['host']) || empty($cxnOld['user']) || empty($cxnOld['db']))
            $this->set_link($link);
        else {
            $cxnNew = $this->ping(true, $link);
            if (!($cxnOld['host'] == $cxnNew['host'] and $cxnOld['user'] == $cxnNew['user'] and $cxnOld['db'] == $cxnNew['db']))
                $this->set_link($link);
        }
        return $this->get_link();
    }

    # close connection
    public function close(?mysqli $link = null, bool $silent_error = false) : bool {
        try {
            return mysqli_close($link ?? $this->get_link());
        }catch (\Exception $e){
            if(!$silent_error)
                $this->show_exception(3);
        }

        return false;
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
                    USER AS users, db FROM information_schema.processlist", "assoc", "select");
                    $db = $x['db'];
                    $usr = $x['users'];
                    $host = $x['host_short'];
                    if ($ignore_msg == false) $this->show_exception(1, [$db, $usr, $host]);
                }
                else if ($ignore_no_conn == false) $this->show_exception(0);
            }
        } return ["host" => $host, "user" => $usr, "db" => $db];
    }

    public function set_link(mysqli $link): void { self::$link = $link;}

    public function get_link(): ?mysqli { return self::$link ?? null; }
}