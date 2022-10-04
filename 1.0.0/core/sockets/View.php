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
        ViewPainter::instance()->paint($page_data,...$options);
    }
    public function view_const(array $page_data) : void {
        ViewPainter::constants($page_data);
    }
    public function add_domain(array $domains, ?string $custom_view = null, ?\Closure $default_fn = null) : string {
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
            $default_fn($view);

        return $view;
    }
    public function inject_view(string $root = "/", string $get_name = "brick") : string {
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

        $project_root = self::get_site_data('base_no_proto');
        $view = $_GET[$get_name] ?? "";
        $view = str_replace($project_root,"",$view);

        if($root != "/") $view = str_replace(["/$root/","/$root","$root/"],"", $view);

        $view = trim($view,"/");
        return $handle_assets_like_js($view);
    }
}
