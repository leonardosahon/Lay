<?php
declare(strict_types=1);
namespace Lay\libs;

use Lay\core\LayConfig;
use Lay\orm\SQL;

/**
 * Store session as cookie through accurate environment storage and encrypted storage token.
 * @example
 * \Lay\libs\LayCookieStorage::save_to_db("my-cookie"): void;
 * \Lay\libs\LayCookieStorage::check_db(): array;
 * \Lay\libs\LayCookieStorage::clear_from_db(): bool;
 * @uses \Wolfcast\BrowserDetection https://wolfcast.com/
 */
abstract class LayCookieStorage {
    private static string $session_user_cookie;
    private static string $table =  "lay_cookie_storages";
    private static function init() : void {
        if(!isset(self::$session_user_cookie))
            self::$session_user_cookie = "_". substr(
                openssl_encrypt(self::lay()->get_site_data('name','short'), "AES-256-CTR", self::$table,0,"h7y6367637673773")
                ,0,10
            );

        self::create_table();
    }
    private static function lay (): LayConfig {
        return LayConfig::instance();
    }

    private static function create_table() : void {
        $table = self::$table;
        $table_exists = true;

        try {
            $table_exists = self::orm()->open(self::$table)->catch()->clause("LIMIT 1")->select();
        }catch (\Exception $e){}

        if($table_exists)
            return;

        self::orm()->query("CREATE TABLE IF NOT EXISTS $table (
                id varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                created_by varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
                created_at datetime NOT NULL,
                deleted int(1) DEFAULT 0 NOT NULL,
                deleted_by varchar(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL NULL,
                deleted_at datetime DEFAULT NULL NULL,
                env_info text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL NULL,
                auth text CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci DEFAULT NULL NULL,
                expire datetime DEFAULT NULL NULL,
                CONSTRAINT `PRIMARY` PRIMARY KEY (id),
                CONSTRAINT {$table}_uid UNIQUE KEY (id)
            )
            ENGINE=InnoDB
            DEFAULT CHARSET=utf8mb4
            COLLATE=utf8mb4_general_ci;
        ");
    }

    private static function orm() : SQL {
        return SQL::instance();
    }

    private static function set_cookie(string $name, string $value, array $options = [] ) : bool {
        extract([
            'expires' => $options['expires'] ?? "30 days",
            'path' => $options['path'] ?? "/",
            'domain' => $options['domain'] ??  null,
            'httponly' => $options['httponly'] ??  false,
            'samesite' => $options['samesite'] ??  "Lax",
            'secure' => $options['secure'] ??  null,
        ]);

        if (self::lay()::get_env() == "dev")
            $secure = $secure ?? false;

        $name = str_replace(["=", ",", ";", " ", "\t", "\r", "\n", "\013","\014"],"",$name);

        return setcookie($name, $value, [
            "expires" => $expires == 0 ? (int) $expires : strtotime($expires),
            "path" => $path,
            "domain" => $domain ?? self::lay()->get_site_data('base'),
            "secure" => $secure ?? true,
            "httponly" => $httponly,
            "samesite" => $samesite
        ]);
    }

    private static function destroy_cookie($name) : void {
        self::set_cookie($name, "", ["expires" => "now",]);
    }

    private static function decrypted_cookie() : ?string {
        self::init();
        $cookie = $_COOKIE[self::$session_user_cookie] ?? null;
        if(!$cookie) return null;
        return Password::crypt($cookie, false);
    }

    private static function delete_expired_tokens() : void {
        $today = LayDate::date();
        self::orm()->open(self::$table)->delete("DATEDIFF('$today',`expire`) > 30");
    }

    private static function store_user_token(string $user_id, string $hashed_pass) : ?array {
        $orm = self::orm();
        $env_info = self::browser_info() . " IP: " . self::get_ip();
        $expire = LayDate::date("30 days");
        $now = LayDate::date();
        $user_id = $orm->clean($user_id,16,'strict');

        self::delete_expired_tokens();

        $token = self::get_user_token($user_id);

        if(!empty($token))
            return $token;

        $token = $orm->uuid();

        $orm->open(self::$table)->then_insert([
            "id" => $token,
            "created_by" => $user_id,
            "created_at" => $now,
            "auth" => $hashed_pass,
            "expire" => $expire,
            "env_info" => $env_info
        ]);

        return ["id" => $token, "auth" => $hashed_pass];
    }

    private static function get_user_token(string $token) : array {
        $orm = self::orm();
        $env_info = $orm->clean(self::browser_info(),11, "PREVENT_SQL_INJECTION");
        $today = LayDate::date();
        $token = $orm->clean($token,16,'strict');

        return $orm->open(self::$table)
            ->column("id,auth")
            ->then_select("WHERE id='$token' AND env_info='$env_info' AND DATEDIFF('$today',`expire`) < 31");
    }

    private static function save_user_token(string $user_id) : ?array {
        $today = LayDate::date();
        $token = self::get_user_token($user_id);
        $hashed_pass = Password::hash($user_id);

        if(!empty($token)) {
            self::orm()->open(self::$table)
                ->column([
                    "expire" => $today,
                    "auth" => $hashed_pass
                ])
                ->then_update("WHERE id='{$token['id']}'");

            return [
                "id" => $token['id'],
                "auth" => $hashed_pass
            ];
        }
        return self::store_user_token($user_id,$hashed_pass);
    }

    private static function delete_user_token(string $token) : void {
        $token = self::orm()->clean($token,11,'PREVENT_SQL_INJECTION');
        self::orm()->open(self::$table)->delete("id='$token'");
    }

    public static function save_to_db(string $immutable_key) : bool {
        self::init();

        return self::set_cookie(
            self::$session_user_cookie,
            Password::crypt(LayCookieStorage::save_user_token($immutable_key)['id'])
        );
    }

    public static function clear_from_db() : void {
        self::init();

        if($token = self::decrypted_cookie())
            LayCookieStorage::delete_user_token($token);

        self::destroy_cookie(self::$session_user_cookie);
    }

    public static function check_db() : array {
        if($token = self::decrypted_cookie())
            return self::get_user_token($token);

        return [];
    }

    public static function save(string $cookie_name, string $cookie_value, string $expire="30 days", string $path="/", ?string $domain = null, ?bool $secure = null, ?bool $http_only = null, ?string $same_site = null) : bool {
        return self::set_cookie($cookie_name, $cookie_value,
            [
                "expires" => $expire,
                "path" => $path,
                "domain" => $domain,
                "secure" => $secure,
                "httponly" => $http_only,
                "samesite" => $same_site,
            ]
        );
    }

    public static function clear(string $cookie_name) : void {
        self::destroy_cookie($cookie_name);
    }

    public static function browser_info() : string {
        $root = self::lay()->get_res__server('root');

        if(file_exists($root . "Lay/vendor/autoload.php"))
            require_once  $root . "Lay/vendor/autoload.php";
        else
            require_once  $root . "vendor/autoload.php";

        $browser = new \Wolfcast\BrowserDetection();
        return $browser->getName() . " " . $browser->getPlatform() . " " . $browser->getUserAgent();
    }

    public static function get_ip(): string {
        $ip_address = $_SERVER['REMOTE_ADDR'];
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

        return $ip_address;
    }
}