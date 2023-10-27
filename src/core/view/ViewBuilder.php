<?php
declare(strict_types=1);
namespace Lay\core\view;

use Closure;
use JetBrains\PhpStorm\ExpectedValues;
use Lay\core\Exception;
use Lay\core\LayConfig;
use Lay\core\traits\IsSingleton;
use Lay\core\view\enums\DomainType;

// TODO: Find a way to cache views
final class ViewBuilder {
    use IsSingleton;
    const DEFAULT_ROUTE = "*";
    private static bool $in_init = false;
    private static bool $redirecting = false;
    private static bool $href_set = false;
    private static string $redirect_url;
    private static bool $alias_checked = false;
    private static array $current_route_data;
    private static string $route;
    private static array $route_aliases;
    private static array $route_container;
    private static bool $view_found = false;
    const route_storage_key = "__LAY_VIEWS__";
    const view_constants = "__LAY_VIEW_PRELUDE__";

    private function store_page_data(string $section, ?string $key = null, mixed $value = null) : self {
        if(self::$view_found)
            return $this;

        if(self::$in_init) {
            if(empty($key)) {
                self::$route_container[self::route_storage_key][self::view_constants][$section] = $value;
                return $this;
            }

            self::$route_container[self::route_storage_key][self::view_constants][$section][$key] = $value;
            return $this;
        }

        if(!isset(self::$route))
            Exception::throw_exception("No valid route found", "NoRouteFound");

        if(empty($key)) {
            self::$route_container[self::route_storage_key][self::$route][$section] = $value;
            return $this;
        }

        self::$route_container[self::route_storage_key][self::$route][$section][$key] = $value;
        return $this;
    }

    public function request(#[ExpectedValues(['route','route_as_array','domain_type','domain_id','pattern','*'])] string $key) : DomainType|string|array
    {
        if(!isset(self::$current_route_data))
            self::$current_route_data = ViewDomain::current_route_data("*");

        if($key == "*")
            return self::$current_route_data;

        return self::$current_route_data[$key];
    }

    public function get_all_routes() : array {
        return self::$route_container[self::route_storage_key] ?? [];
    }

    public function get_route_details(string $route) : ?array {
        return self::$route_container[self::route_storage_key][$route] ?? null;
    }

    public function connect_db() : self {
        LayConfig::connect();
        return $this;
    }

    public function init_start() : self {
        self::$in_init = true;

        if(!self::$href_set) {
            self::$href_set = true;
            $this->local("href", fn(?string $href = "", ?string $domain_id = null) => $this->href_link($href, $domain_id));
        }

        return $this;
    }

    private function store_constants() : void {
        ViewEngine::constants($this->get_route_details(self::view_constants) ?? []);
    }

    private function get_constants() : array {
        return $this->get_route_details(self::view_constants) ?? [];
    }

    private function bind_uri() : string {
        $data = $this->request('*');

        if(empty($data['route_as_array'][0]))
            $data['route_as_array'][0] = 'index';

        foreach ([self::$route, ...self::$route_aliases] as $route) {
            self::$route = $route;
            $uri = explode("/", self::$route);
            $uri_size = count($uri);

            if (count($data['route_as_array']) == $uri_size) {
                foreach ($uri as $i => $u) {
                    $current_uri = $data['route_as_array'][$i];

                    if (str_starts_with($u, "{")) {
                        $data['route_as_array'][$i] = $u;
                        continue;
                    }

                    if ($current_uri != $u)
                        break;
                }

                $data['route'] = implode("/", $data['route_as_array']);
                break;
            }
        }

        return $data['route'];
    }

    private function href_link(?string $link = "", ?string $domain_id = null) : string {
        $req = $this->request('*');
        $link = is_null($link) ? '' : $link;
        $base = LayConfig::site_data();
        $base_full = $base->base;

        if($domain_id) {
            $same_domain = $domain_id == $this->request('domain_id');
            $domain_id = ViewDomain::new()->get_domain_by_id($domain_id);

            $req['pattern'] = $domain_id ? $domain_id['patterns'][0] : "*";

            if($req['pattern'] != "*" && LayConfig::$ENV_IS_PROD) {
                $x = explode(".", $base->base_no_proto, 2);
                $base_full = $base->proto . "://" . $req['pattern'] . "." . end($x) . "/";
                $req['pattern'] = "*";
            }

            if(!$same_domain && $req['domain_type'] == DomainType::SUB) {
                $x = explode(".", $base->base_no_proto, 2);
                $base_full = $base->proto . "://" . end($x) . "/";
            }
        }

        $domain = $req['pattern'] == "*" ? "" : $req['pattern'];

        if($req['domain_type'] == DomainType::LOCAL)
            $domain = $domain ? $domain . "/" : $domain;
        else
            $domain = "";

        return $base_full . $domain . $link;
    }

    public function init_end() : void {
        self::$in_init = false;
        $this->store_constants();
    }

    public function end() : void {
        if(self::$view_found)
            return;

        ViewEngine::new()->paint($this->get_route_details("*"));
    }

    public function route(string $route, string ...$aliases) : self {
        self::$route_aliases = [];
        self::$alias_checked = false;

        if(self::$view_found)
            return $this;

        self::$route = trim($route, "/");
        self::$route_aliases = $aliases;

        return $this;
    }

    public function redirect(string $route, ?Closure $action = null, bool $end_after_action = false) : void {
        self::$redirecting = true;
        self::$redirect_url = $route;

        $caller = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['class'];

        if(!is_null($action)) {
            $action();

            if($end_after_action)
                return ;
        }

        (new $caller)->init();
    }

    public function bind(Closure $handler) : self {
        // Cache default page
        if(self::$route == self::DEFAULT_ROUTE)
            $handler($this, $this->get_constants());

        if(self::$view_found)
            return $this;

        $route = self::$redirecting ? self::$redirect_url : $this->bind_uri();

        if(self::$route == $route) {
            if($route == self::DEFAULT_ROUTE)
                return $this;

            $handler($this, $this->get_constants(), self::$route, self::$route_aliases);
            $current_page = $this->get_route_details($route) ?? [];

            self::$view_found = true;

            if(isset($current_page['page']['title']))
                ViewEngine::new()->paint($current_page);
        }

        return $this;
    }

    public function core(string $key, bool $value) : self {
        return $this->store_page_data(ViewEngine::key_core, $key, $value);
    }

    public function page(string $key, ?string $value) : self {
        return $this->store_page_data(ViewEngine::key_page, $key, $value);
    }

    public function body_tag(?string $class = null, ?string $attribute = null) : self {
        return $this->store_page_data(ViewEngine::key_body, value: ["class" => $class, "attr" => $attribute]);
    }

    public function head(string|Closure $file_or_func) : self {
        return $this->store_page_data(ViewEngine::key_view, 'head', $file_or_func);
    }

    public function body(string|Closure $file_or_func) : self {
        return $this->store_page_data(ViewEngine::key_view, 'body', $file_or_func);
    }

    public function script(string|Closure $file_or_func) : self {
        return $this->store_page_data(ViewEngine::key_view, 'script', $file_or_func);
    }

    public function assets(string|array ...$assets) : self {
        return $this->store_page_data(ViewEngine::key_assets, value: $assets);
    }

    public function local(string $key, mixed $value) : self {
        return $this->store_page_data(ViewEngine::key_local, $key, $value);
    }

    public function local_array(string $key, mixed $value) : self {
        return $this->store_page_data(ViewEngine::key_local_array, $key, $value);
    }

}
