<?php
declare(strict_types=1);
namespace Lay\core\sockets;
use JetBrains\PhpStorm\ExpectedValues;
use Lay\core\enums\CustomContinueBreak;
use Lay\core\enums\DomainCacheKeys;
use Lay\core\enums\DomainType;
use Lay\core\ViewPainter;
use Lay\core\ViewTemplate;

trait Domain {
    private static string $current_route;
    private static array $current_route_details = [
        "route" => "index",
        "route_as_array" => [],
        "domain_type" => DomainType::LOCAL,
        "pattern" => "*",
    ];
    private static bool $domain_found = false;
    private static string $domains_list_key = "__LAY_DOMAINS__";

    private function domain_cache_key(DomainCacheKeys $key_type, string|null|int $key = null, mixed $value = null, bool $cache = true) : mixed {
        $cache = $this->get_site_data("cache_domains") && $cache;

        if($value) {
            if($key) {
                if($cache && isset($_SESSION[self::$domains_list_key][$key_type->value][$key]))
                    return null;

                $_SESSION[self::$domains_list_key][$key_type->value][$key] = $value;
                return null;
            }


            if($cache && isset($_SESSION[self::$domains_list_key][$key_type->value]))
                return null;

            $_SESSION[self::$domains_list_key][$key_type->value] = $value;
            return null;
        }

        if($key)
            return $_SESSION[self::$domains_list_key][$key_type->value][$key] ?? null;

        return $_SESSION[self::$domains_list_key][$key_type->value] ?? null;
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

    private function activate_domain(string $id, string $pattern, ViewTemplate $handler, DomainType $domain_type) : CustomContinueBreak {
        $route = $this->get_current_route();
        $route = str_replace($pattern, "", $route);
        $route = ltrim($route, "/");
        $route_as_array = explode("/", $route);

        self::$domain_found = true;
        $this->cache_active_domain($id, $pattern);

        self::$current_route_details['route'] = $route ?: "index";
        self::$current_route_details['route_as_array'] = $route_as_array;
        self::$current_route_details['pattern'] = $pattern;
        self::$current_route_details['domain_type'] = $domain_type;

        $handler->init();

        return CustomContinueBreak::BREAK;
    }

    /**
     * If route its static file (jpg, json, etc.) this means the webserver (apache) was unable to locate the file,
     * hence a 404 error should be returned.
     * But if it's not a static file, the route should be returned instead
     * @param string $view
     * @return string
     */
    private function check_route_is_static_file(string $view) : string {
        $ext_array = ["js","css","map","jpeg","jpg","png","gif","jiff","svg","json","xml","yaml"];
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
        self::is_init();

        if(isset(self::$current_route))
            return self::$current_route;

        //--START PARSE URI
        $root = "/";
        $get_name = "brick";

        $root_url = self::get_site_data('base_no_proto');
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
        $base = $this->get_site_data('base_no_proto');
        $sub_domain = explode(".", $base, 2);
        $local_dir = explode("/", self::$current_route, 2);

        return [
            "sub" => [
                "value" => $sub_domain[0],
                "found" => count($sub_domain) > 1,
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
        $is_subdomain = $domain['sub']['found'] && $domain['sub']['value'] == $pattern;

        // This conditions handles virtual folder.
        // This is a situation were the developer wants to separate various sections of the application into folders.
        // The dev doesn't necessarily have to create folders, hence "virtual folder".
        // All the dev needs to do is map the pattern to a view handler
        //
        // Example:
        //  localhost/example.com/admin/;
        //  localhost/example.com/clients/;
        //  localhost/example.com/vendors;
        //
        // This condition is looking out for "/admin" || "/clients" || "/vendors" in the `patterns` argument.
        $is_local_domain = $domain['local']['found'] && $domain['local']['value'] == $pattern;

        if($is_subdomain || $is_local_domain) {

            $handler = $this->get_cached_domain_details($id)['handler'];
            $this->activate_domain($id, $pattern, $handler, $is_subdomain ? DomainType::SUB : DomainType::LOCAL);
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

            if($id == "default" || $pattern == "*"){
                $handler = $this->get_cached_domain_details($id)['handler'];
                $this->activate_domain($id, $pattern, $handler, DomainType::LOCAL);
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

    public function add_domain(string $id, array $patterns, ViewTemplate $handler) : void {
        self::is_init();

        $this->get_current_route();

        if($this->match_cached_domains())
            return;

        $this->cache_domain_details([
            "id" => $id,
            "patterns" => $patterns,
            "handler" => $handler
        ]);

        $this->cache_patterns($id, $patterns);
    }

    public static function current_route_data(#[ExpectedValues(['route','route_as_array','domain_type','pattern', '*'])] string $key) : string|DomainType|array
    {
        if($key == "*")
            return self::$current_route_details;

        return self::$current_route_details[$key];
    }

    /**
     * @param array $page_data
     * @see ViewPainter
     */
    public function view(array $page_data) : void {
        self::is_init();
        ViewPainter::instance()->paint($page_data);
    }

    public function view_const(array $page_data) : void {
        self::is_init();
        ViewPainter::constants($page_data);
    }

}
