<?php
declare(strict_types=1);
namespace Lay\core\sockets;
use Lay\core\ViewPainter;

trait View{
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

    /**
     * @param array $domains a function that accepts ($view, $current_domain) as args
     * $view: This is the current view url; e.g A Homepage view can be "index"; About Us Page can be "about"
     * $current_domain: This variable holds each domain alias, so you can use it for further processing
     <br>
        [
            {domain}|{domain alias}|{domain alias}|..." => function ($view, $current_domain) : string {
                return $view;
            },
         ]
     * @param string|null $custom_view url to another view not captured by the default query strung `$_GET['f']`
     * @param \Closure|null $default_fn default function to execute on domains not listed in the domain array parameter
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
                if(str_starts_with($host, "/")) {
                    $host = ltrim($host, "/");

                    if(str_starts_with($view, $host)){
                        $view = str_replace($host,"", $view);
                        $domain = $host;
                        $found = true;
                        break;
                    }
                    continue;
                }

                $host = $proto . $host;
                if(str_starts_with($base, $host)){
                    $view = str_replace($host,"", $view);
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

        if($default_fn){
            $view = ltrim($view, "/");
            $default_fn($view,explode("/",$view));
        }

        return $view;
    }
    public function inject_view(string $root = "/", string $get_name = "brick") : string {
        self::is_init();
        $handle_assets_like_js = function ($view){
            $ext_array = ["js","css","map","jpeg","jpg","png","gif","jiff","svg","json","xml","yaml"];
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
        $root_file_system = rtrim(explode("index.php",$_SERVER['SCRIPT_NAME'])[0],"/");

        $view = str_replace("/index.php","",$_GET[$get_name] ?? "");
        $view = str_replace([$root_url,$root_file_system],"",$view);

        if($root != "/") $view = str_replace(["/$root/","/$root","$root/"],"", $view);

        $view = trim($view,"/");
        return $handle_assets_like_js($view);
    }
}
