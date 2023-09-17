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
            self::$session_user_cookie = "lay_cok_" . self::orm()->clean(self::lay()->get_site_data('name','short'),6);

        self::create_table();
    }
    private static function lay (): LayConfig {
        return LayConfig::instance();
    }

    private static function create_table() : void {
        $table = self::$table;

        // check if table exists, but catch the error
        self::orm()->open(self::$table)->catch()->clause("LIMIT 1")->select();

        // Check if the above query had an error. If no error, table exists, else table doesn't exist
        if(self::orm()->query_info['has_error'] === false)
            return;

        self::orm()->query("CREATE TABLE IF NOT EXISTS `$table` (
                `id` varchar(36) UNIQUE PRIMARY KEY,
                `created_by` varchar(36) NOT NULL,
                `created_at` datetime,
                `deleted` int(1) DEFAULT 0 NOT NULL,
                `deleted_by` varchar(36),
                `deleted_at` datetime,
                `env_info` text,
                `auth` text,
                `expire` datetime
            )
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

        if (self::lay()::$ENV_IS_DEV)
            $secure = $secure ?? false;

        $name = str_replace(["=", ",", ";", " ", "\t", "\r", "\n", "\013","\014"],"",$name);

        setcookie($name, $value, [
            "expires" => $expires == 0 ? (int) $expires : strtotime($expires),
            "path" => $path,
            "domain" => $domain ?? $_SERVER['HTTP_HOST'],
            "secure" => $secure ?? true,
            "httponly" => $httponly,
            "samesite" => $samesite
        ]);

        return isset($_COOKIE[$name]);
    }

    private static function store_user_token(string $user_id) : ?string {
        $orm = self::orm();
        $env_info = self::browser_info() . " IP: " . LayConfig::get_ip();
        $expire = LayDate::date("30 days");
        $now = LayDate::date();
        $user_id = $orm->clean($user_id,16,'strict');

        self::delete_expired_tokens();

        $orm->open(self::$table)->then_insert([
            "id" => "UUID()",
            "created_by" => $user_id,
            "created_at" => $now,
            "auth" => LayPassword::hash($user_id),
            "expire" => $expire,
            "env_info" => $env_info
        ]);

        return $orm->table(self::$table)->last_item("created_at")['id'];
    }

    private static function save_user_token(string $user_id) : ?string {
        $today = LayDate::date();
        $data = self::validate_cookie()['data'];

        if(!empty($data)) {
            self::orm()->open(self::$table)
                ->column(["expire" => $today])
                ->then_update("WHERE created_by='{$data['created_by']}' AND auth='{$data['auth']}'");
            return null;
        }

        return self::store_user_token($user_id);
    }

    private static function get_user_token(string $id) : array {
        $orm = self::orm();
        $id = $orm->clean($id,16,'strict');

        return $orm->open(self::$table)
            ->column("created_by, auth")
            ->then_select("WHERE id='$id'");
    }

    private static function destroy_cookie($name) : void {
        self::set_cookie($name, "", ["expires" => "now",]);
    }

    private static function decrypt_cookie() : ?string {
        self::init();

        if(!isset($_COOKIE[self::$session_user_cookie]))
            return null;

        $cookie = $_COOKIE[self::$session_user_cookie] ?? null;

        if(!$cookie)
            return null;

        return LayPassword::crypt($cookie, false);
    }

    private static function delete_expired_tokens() : void {
        $today = LayDate::date();
        self::orm()->open(self::$table)->delete("DATEDIFF('$today',`expire`) > 30");
    }

    private static function delete_user_token(string $token_id) : void {
        $token_id = self::orm()->clean($token_id,11,'PREVENT_SQL_INJECTION');
        self::orm()->open(self::$table)->delete("id='$token_id'");
    }


    /*
     * ### PUBLIC ###
     */

    public static function save_to_db(string $immutable_key) : bool {
        self::init();

        self::delete_expired_tokens();

        if(isset($_COOKIE[self::$session_user_cookie]))
            return true;


        return self::set_cookie(
            self::$session_user_cookie,
            LayPassword::crypt(LayCookieStorage::save_user_token($immutable_key))
        );
    }

    public static function clear_from_db() : void {
        self::init();

        if($id = self::decrypt_cookie())
            LayCookieStorage::delete_user_token($id);

        self::destroy_cookie(self::$session_user_cookie);
    }

    public static function validate_cookie() : array {
        self::init();

        if(!isset($_COOKIE[self::$session_user_cookie]))
            return ["code" => 2, "msg" => "Cookie is not set", "data" => null];

        if($id = self::decrypt_cookie())
            return [
                "code" => 1,
                "msg" => "Cookie found!",
                "data" => self::get_user_token($id)
            ];

        return ["code" => 0, "msg" => "Could not decrypt, invalid token saved", "data" => null];
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
}