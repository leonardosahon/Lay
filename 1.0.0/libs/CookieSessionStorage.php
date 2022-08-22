<?php
declare(strict_types=1);
namespace Lay\libs;

use Lay\AutoLoader;
use Lay\orm\SQL;

/**
 * Store session as cookie through accurate environment storage and encrypted storage token.
 */
class CookieSessionStorage {
    private static string $session_user_cookie;
    private static CookieSessionStorage $instance;
    private static string $table_main =  "system_user_cookie_session_storage";

    private function __construct() {
        $d = explode(DIRECTORY_SEPARATOR,AutoLoader::get_root_dir());
        $i = count($d);
        $salt = trim(str_replace([".","/"],"_",$d[$i-2]));
        self::$session_user_cookie = "_". substr($salt,0,5);
    }
    private function __clone(){}

    private static function orm() : SQL {
        return SQL::instance();
    }
    private static function date() : DateHandler {
        return DateHandler::instance();
    }

    private function set_cookie(string $cookieName, string $cookieValue, string $expire="30 days", string $path="/", ...$opts) : bool {
        $domain = $opts[0] ?? $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_HOST'];
        $secure = $opts[1] ?? null;
        $http_only = $opts[2] ?? false;
        $same_site = $opts[3] ?? "Lax";

        if ($domain == "127.0.0.1" or $domain == "localhost") {
            $secure = $secure ?? false;
            $domain = $_SERVER['HTTP_HOST'];
        }

        $secure = $secure ?? true;

        if($expire == 0)
            $expire = (int) $expire;
        else
            $expire = strtotime($expire);

        return setcookie($cookieName, $cookieValue, [
            "expires" => $expire,
            "path" => $path,
            "domain" => $domain,
            "secure" => $secure,
            "httponly" => $http_only,
            "samesite" => $same_site
        ]);
    }

    private function destroy_cookie($cookieName) : bool {
        return $this->set_cookie($cookieName,"","now");
    }

    private function decrypted_cookie() : ?string {
        $cookie = $_COOKIE[self::$session_user_cookie] ?? null;
        if(!$cookie) return null;
        return (new Crypt())->toggleCrypt($cookie, false);
    }

    private function delete_expired_tokens() : void {
        $today = self::date()->date();
        self::orm()->del(self::$table_main,"DATEDIFF('$today',`expire`) > 30");
    }

    private function store_user_token(string $user_id, string $encrypted_password) : ?array {
        $osai = self::orm();
        $env_info = $this->browser_env_info();
        $date = self::date();
        $expire = $date->date("30 days");
        $now = $date->date();
        $user_id = $osai->clean($user_id,16,'strict');

        $this->delete_expired_tokens();

        if($token = $this->get_user_token($user_id))
            return $token;

        $token = $osai->get("UUID()")[0];
        $osai->add(self::$table_main,"entity_guid='$token',act_by='$user_id',act_date='$now',
            user_id='$user_id',env_info='$env_info',auth='$encrypted_password',expire='$expire'");

        return $osai->last_col("entity_guid,user_id,auth",self::$table_main);
    }

    private function get_user_token(string $token) : ?array {
        $osai = self::orm();
        $env_info = $this->browser_env_info();
        $today = self::date()->date();
        $token = $osai->clean($token,16,'strict');
        return $osai->get("entity_guid,user_id,auth",self::$table_main,
            "WHERE entity_guid='$token' AND env_info='$env_info' AND DATEDIFF('$today',`expire`) < 31","assoc");
    }

    private function update_user_token(string $user_id, string $encrypted_password) : ?array {
        $osai = self::orm();
        $today = self::date()->date();
        if($token = $this->get_user_token($user_id)) {
            $token = $token['entity_guid'];
            $osai->update(self::$table_main, "expire='$today',auth='$encrypted_password'", "WHERE entity_guid='$token'");
            $token['auth'] = $encrypted_password;
            return $token;
        }
        return $this->store_user_token($user_id,$encrypted_password);
    }

    private function delete_user_token(string $token) : void {
        $osai = SQL::instance();
        $token = $osai->clean($token,16,'strict');
        $osai->del(self::$table_main,"entity_guid='$token'");
    }

    #-- PUBLIC ----------------------------------
    public static function instance() : self {
        if(!isset(self::$instance))
            self::$instance = new CookieSessionStorage();
        return self::$instance;
    }

    public static function create_table() : self {
        $osai = self::orm();
        $table = self::$table_main;
        $osai->query("CREATE TABLE IF NOT EXISTS $table (
                id int(11) auto_increment NOT NULL,
                entity_guid varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                act_by varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                act_date datetime NOT NULL,
                view_status int(1) DEFAULT 0 NOT NULL,
                delete_by varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL NULL,
                delete_date datetime DEFAULT NULL NULL,
                user_id varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL NULL,
                env_info text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL NULL,
                auth text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL NULL,
                expire datetime DEFAULT NULL NULL,
                CONSTRAINT `PRIMARY` PRIMARY KEY (id),
                CONSTRAINT {$table}_entity_guid_IDX UNIQUE KEY (entity_guid)
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_general_ci;
        ");
        echo "Table created successfully<br>";
        return self::instance();
    }

    public function set_session_cookie(string $cookie_name) : void {
        self::$session_user_cookie = self::$session_user_cookie . "_" . $cookie_name;
    }

    public function store_cookie(string $user_id, string $encrypted_password) : bool {
        $token = $this->update_user_token($user_id,$encrypted_password);
        return $this->set_cookie(self::$session_user_cookie, (new Crypt())->toggleCrypt($token['entity_guid']));
    }

    public function check_cookie() : ?array {
        $token = $this->decrypted_cookie();
        return $token ? $this->get_user_token($token) : null;
    }

    public function delete_cookie() : void {
        if($token = $this->decrypted_cookie()) $this->delete_user_token($token);
        $this->destroy_cookie(self::$session_user_cookie);
    }

    public function browser_env_info() : string {
        $root = AutoLoader::get_root_dir();
        if(file_exists($root . "Lay/vendor/autoload.php"))
            require_once  $root . "Lay/vendor/autoload.php";
        else
            require_once  $root . "vendor/autoload.php";


        $browser = new \Wolfcast\BrowserDetection();
        return $browser->getName() . " " . $browser->getPlatform() . " " . $browser->getUserAgent();
    }

    public function get_ip_address(): string {
        $ip_address = $_SERVER['REMOTE_ADDR'];
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'] as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip_address) {
                    $ip_address = trim($ip_address);

                    if (filter_var($ip_address, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false)
                        return $ip_address;
                }
            }

        }
        return $ip_address;
    }

    public function store_cookie_unsafe(string $cookie_name, string $cookie_value, string $expire="30 days", string $path="/", ?string $domain = null, ?bool $secure = null, ?bool $http_only = null, ?string $same_site = null) : bool {
        return $this->set_cookie($cookie_name, $cookie_value, $expire, $path, $domain,$secure,$http_only,$same_site);
    }

    public function delete_cookie_unsafe(string $cookie_name) : void {
        $this->destroy_cookie($cookie_name);
    }
}