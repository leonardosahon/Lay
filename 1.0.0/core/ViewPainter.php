<?php
declare(strict_types=1);
namespace Lay\core\sockets;
use Lay\core\ViewPainter;

trait View{
    /**
     * @param array $page_data
     * @param ...$options
     * @see ViewPainter
     */
    public function view(array $page_data, ...$options) : void {
        self::is_init();
        ViewPainter::instance()->paint($page_data,...$options);
    }
    public function view_const(array $page_data) : void {
        self::is_init();
        ViewPainter::constants($page_data);
    }

    /**
     * @param array $domains
        [
            {domain}|{domain alias 1}|{domain alias 2}|..." => function ($view, $key) {
                return $view;
            },
         ]
     * @param string|null $custom_view
     * @param \Closure|null $default_fn
     * @return string
     */
    public function add_domain(array $domains, ?string $custom_view = null, ?\Closure $default_fn = null) : string {
        self::is_init();
        $layConfig = self::instance();
        $base = $layConfig->get_site_data('base');
        $proto = $layConfig->get_site_data('proto') . "://";
        $domain_host = array_keys($domains);
        list($domain_key, $domain) = "";
        $found = false;
        $view = $custom_view ?? $this->inject_view() ?: ($_GET['f'] ?? "index");

        foreach ($domain_host as $dmn) {
            if($found)
                break;

            $domain_key = $dmn;
            if($dmn == "_" || is_int($dmn))
                continue;

            foreach (explode("|", $dmn) as $host){
                if(substr($host,0,1) == "/") {
                    $host = ltrim($host, "/");

                    if(substr($view,0, strlen($host)) == $host){
                        $domain = $host;
                        $found = true;
                        break;
                    }
                    continue;
                }

                $host = $proto . $host;
                if(substr($base,0, strlen($host)) == $host){
                    $domain = $host;
                    $found = true;
                    break;
                }
            }
        }

        if(!empty($domains))
            $view = $domains[$domain_key]($view,$domain);

        if(empty($view))
            $view = "index";

        if($default_fn)
            $default_fn($view,explode("/",$view));

        return $view;
    }
    public function inject_view(string $root = "/", string $get_name = "brick") : string {
        self::is_init();
        $handle_assets_like_js = function ($view){
            $ext_array = ["js","css","map","jpeg","jpg","png","gif","jiff","svg"];
            $x = explode(".",$view);
            $ext = strtolower(end($x));

            if(count($x) > 1 && in_array($ext,$ext_array,true)) {
                http_response_code(404);
                echo "{error: 404, response: 'resource not found'}";
                die;
            }

            return $view;
        };

        $root_url = self::get_site_data('base_no_proto');
        $root_file_system = ltrim(explode("index.php",$_SERVER['SCRIPT_NAME'])[0],"/");

        $view = $_GET[$get_name] ?? "";
        $view = str_replace([$root_url,$root_file_system],"",$view);

        if($root != "/") $view = str_replace(["/$root/","/$root","$root/"],"", $view);

        $view = trim($view,"/");
        return $handle_assets_like_js($view);
    }
}
