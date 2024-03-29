<?php
declare(strict_types=1);
namespace Lay\core\view;
use JetBrains\PhpStorm\ExpectedValues;
use Lay\core\enums\CustomContinueBreak;
use Lay\core\LayConfig;
use Lay\core\traits\IsSingleton;
use Lay\core\view\enums\DomainCacheKeys;
use Lay\core\view\enums\DomainType;

class ViewDomain {
    use IsSingleton;

    private static string $current_route;
    private static array $current_route_details = [
        "route" => "index",
        "route_as_array" => [],
        "domain_type" => DomainType::LOCAL,
        "pattern" => "*",
        "domain_id" => "",
    ];

    private static bool $lay_init = false;
    private static LayConfig $layConfig;
    private static object $site_data;
    private static bool $cache_domains = true;
    private static bool $cache_domain_set = false;
    private static bool $domain_found = false;
    private static string $domain_list_key = "__LAY_DOMAINS__";
    private static array $domain_ram;

    private static function init_lay() : void {
        if(self::$lay_init)
            return;

        LayConfig::is_init();
        self::$layConfig = LayConfig::new();
        self::$site_data = self::$layConfig::site_data();

        self::$lay_init = true;
    }

    private static function init_cache_domain() : void {
        if(self::$cache_domain_set)
            return;

        self::$cache_domain_set = true;
        self::$cache_domains = self::$layConfig::$ENV_IS_PROD && self::$site_data->cache_domains;
    }

    private function cache_domain_ram() : void {
        if (self::$cache_domains)
            $_SESSION[self::$domain_list_key] = self::$domain_ram;
    }

    private function read_cached_domain_ram() : void {
        if(self::$cache_domains && isset($_SESSION[self::$domain_list_key]))
            self::$domain_ram = $_SESSION[self::$domain_list_key];
    }

    private function domain_cache_key(DomainCacheKeys $key_type, string|null|int $key = null, mixed $value = null, bool $cache = true) : mixed {
        $cache = $cache && self::$cache_domains;
        $this->read_cached_domain_ram();

        if($value) {
            if($key) {
                if($cache && isset(self::$domain_ram[$key_type->value][$key]))
                    return null;

                self::$domain_ram[$key_type->value][$key] = $value;
                $this->cache_domain_ram();
                return null;
            }

            if($cache && isset(self::$domain_ram[$key_type->value]))
                return null;

            self::$domain_ram[$key_type->value] = $value;
            $this->cache_domain_ram();
            return null;
        }

        if($key)
            return self::$domain_ram[$key_type->value][$key] ?? null;

        return self::$domain_ram[$key_type->value] ?? null;
    }

    private function cache_domain_details(array $domain) : void {
        if($this->domain_cache_key(DomainCacheKeys::List, $domain['id']))
            return;

        $this->domain_cache_key(DomainCacheKeys::List, $domain['id'], $domain);
    }

    private function get_cached_domain_details(string $id) : ?array {
        return $this->domain_cache_key(DomainCacheKeys::List, $id);
    }

    private function cache_active_domain(string $id, string $domain_pattern) : void {
        $data = $this->get_active_domain();

        $this->domain_cache_key(DomainCacheKeys::CURRENT, value: ["pattern" => $domain_pattern, "id" => $id], cache: $data && $data['pattern'] == $domain_pattern);
    }

    private function get_active_domain() : ?array {
        return $this->domain_cache_key(DomainCacheKeys::CURRENT);
    }

    private function cache_all_domain_ids(string $id, string $domain_pattern) : void {
        $this->domain_cache_key(DomainCacheKeys::ID, $domain_pattern, $id);
    }

    private function get_all_domain_ids() : ?array {
        return $this->domain_cache_key(DomainCacheKeys::ID);
    }

    private function all_domain_is_cached() : void {
        $this->domain_cache_key(DomainCacheKeys::CACHED, value: true);
    }

    private function is_all_domain_cached() : ?bool {
        return $this->domain_cache_key(DomainCacheKeys::CACHED);
    }

    private function activate_domain(string $id, string $pattern, ViewBuilderStarter $builder, DomainType $domain_type) : void {
        $route = $this->get_current_route();
        $route = explode($pattern, $route, 2);
        $route = ltrim(end($route), "/");
        $route_as_array = explode("/", $route);

        self::$domain_found = true;
        $this->cache_active_domain($id, $pattern);

        self::$current_route_details['route'] = $route ?: "index";
        self::$current_route_details['route_as_array'] = $route_as_array;
        self::$current_route_details['pattern'] = $pattern;
        self::$current_route_details['domain_type'] = $domain_type;
        self::$current_route_details['domain_id'] = $id;

        $builder->init();
    }

    /**
     * If route its static file (jpg, json, etc.) this means the webserver (apache) was unable to locate the file,
     * hence a 404 error should be returned.
     * But if it's not a static file, the route should be returned instead
     * @param string $view
     * @return string
     */
    private function check_route_is_static_file(string $view) : string {
        $ext_array = ["js","css","map","jpeg","jpg","png","gif","jiff","webp","svg","json","xml","yaml","ttf","woff2","woff"];
        $x = explode(".",$view);
        $ext = strtolower((string) end($x));

        if(count($x) > 1 && in_array($ext,$ext_array,true)) {
            http_response_code(404);
            echo "{error: 404, response: 'resource not found'}";
            die;
        }

        return $view;
    }

    /**
     * Gets the request url from the webserver through the `brick` query.
     * It will process it and return the sanitized url, stripping all unnecessary values.
     *
     * If a static asset's uri like (jpg, json) is received,
     * it means the server could not locate the files,
     * hence throw error 404
     *
     * @return string
     */
    private function get_current_route() : string {
        self::init_lay();

        if(isset(self::$current_route))
            return self::$current_route;

        //--START PARSE URI
        $root = "/";
        $get_name = "brick";

        $root_url = self::$site_data->base_no_proto;
        $root_file_system = rtrim(explode("index.php", $_SERVER['SCRIPT_NAME'])[0], "/");

        $view = str_replace("/index.php","",$_GET[$get_name] ?? "");
        $view = str_replace([$root_url,$root_file_system],"",$view);

        if($root != "/")
            $view = str_replace(["/$root/","/$root","$root/"],"", $view);

        //--END PARSE URI

        self::$current_route = $this->check_route_is_static_file(trim($view,"/")) ?: 'index';

        return self::$current_route;
    }

    private function active_pattern() : array {
        $base = self::$site_data->base_no_proto;
        $sub_domain = explode(".", $base, 3);
        $local_dir = explode("/", self::$current_route, 2);

        return [
            "sub" => [
                "value" => $sub_domain[0],
                "found" => count($sub_domain) > 2,
            ],
            "local" => [
                "value" => $local_dir[0],
                "found" => count($local_dir) > 0,
            ],
        ];
    }

    private function test_pattern(string $id, string $pattern) : CustomContinueBreak {
        if(self::$domain_found)
            return CustomContinueBreak::BREAK;

        if(!$this->is_all_domain_cached())
            return CustomContinueBreak::CONTINUE;

        $domain = $this->active_pattern();

        // This condition handles subdomains.
        // If the dev decides to create subdomains,
        // Lay can automatically map the views to the various subdomains as directed by the developer.
        //
        // Example:
        //  https://admin.example.com;
        //  https://clients.example.com;
        //  https://vendors.example.com;
        //
        // This condition is looking out for "admin" || "clients" || "vendors" in the `patterns` argument.
        $is_subdomain = $domain['sub']['found'];

        // This conditions handles virtual folder.
        // This is a situation were the developer wants to separate various sections of the application into folders.
        // The dev doesn't necessarily have to create folders, hence "virtual folder".
        // All the dev needs to do is map the pattern to a view builder
        //
        // Example:
        //  localhost/example.com/admin/;
        //  localhost/example.com/clients/;
        //  localhost/example.com/vendors;
        //
        // This condition is looking out for "/admin" || "/clients" || "/vendors" in the `patterns` argument.
        $is_local_domain = $domain['local']['found'];

        if($is_subdomain && $is_local_domain)
            $is_local_domain = false;

        if($is_subdomain && $domain['sub']['value'] == $pattern) {
            $builder = $this->get_cached_domain_details($id)['builder'];
            $this->activate_domain($id, $pattern, $builder, DomainType::SUB);
            return CustomContinueBreak::BREAK;
        }

        if($is_local_domain && $domain['local']['value'] == $pattern) {
            $builder = $this->get_cached_domain_details($id)['builder'];
            $this->activate_domain($id, $pattern, $builder, DomainType::LOCAL);
            return CustomContinueBreak::BREAK;
        }

        return CustomContinueBreak::FLOW;
    }
    private function match_cached_domains() : bool {
        if(!$this->is_all_domain_cached())
            return false;

        if(self::$domain_found)
            return true;

        $patterns = $this->get_all_domain_ids();

        foreach ($patterns as $pattern => $id) {
            $rtn = $this->test_pattern($id, $pattern);

            if($rtn == CustomContinueBreak::BREAK)
                return true;

            if($id == "default" || $pattern == "*") {
                $builder = $this->get_cached_domain_details($id)['builder'];
                $this->activate_domain($id, $pattern, $builder, DomainType::LOCAL);
            }
        }

        return false;
    }
    private function cache_patterns(string $id, array $patterns) : void {
        foreach ($patterns as $pattern) {
            $this->cache_all_domain_ids($id, $pattern);

            $this->test_pattern($id, $pattern);

            if($id == "default" || $pattern == "*") {
                $this->all_domain_is_cached();
                $this->match_cached_domains();
            }
        }
    }

    public function create(string $id, array $patterns, ViewBuilderStarter $builder) : void {
        self::init_lay();
        self::init_cache_domain();

        $this->get_current_route();

        if($this->match_cached_domains())
            return;

        $this->cache_domain_details([
            "id" => $id,
            "patterns" => $patterns,
            "builder" => $builder
        ]);

        $this->cache_patterns($id, $patterns);
    }

    public static function current_route_data(#[ExpectedValues(['route','route_as_array','domain_type','pattern', '*'])] string $key) : string|DomainType|array
    {
        if($key == "*")
            return self::$current_route_details;

        return self::$current_route_details[$key];
    }

    public function get_domain_by_id(string $id) : ?array {
        return $this->get_cached_domain_details($id);
    }
}
