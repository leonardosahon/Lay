<?php
declare(strict_types=1);
namespace Lay\core;

use Closure;

/**
 * Page Creator
 */
final class ViewPainter {
    private static array $VIEW_ARGS;
    private static array $constant_attributes;
    private static self $instance;

    private function __clone(){}
    private function __construct(){}
    public static function instance() : self {
        if(!isset(self::$instance))
            self::$instance = new self();
        return self::$instance;
    }

    public function paint(array &$meta, ...$meta_args) : void {
        if(empty(self::$constant_attributes))
            self::constants([]);

        $url = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : $_SERVER['REQUEST_URI'];
        $const = self::$constant_attributes;

        $meta = [
            "core" => [
                "close_connection" => $meta['core']['close_connection'] ?? $const['core']['close_connection'],
                "script" => $meta['core']['script'] ?? $const['core']['script'],
                "strict" => $meta['core']['strict'] ?? $const['core']['strict'],
                "skeleton" => $meta['core']['skeleton'] ?? $const['core']['skeleton'],
            ],
            "page" => [
                "charset" =>  $meta['page']['charset'] ?? $const['page']['charset'],
                "base" =>  $meta['page']['base'] ?? $const['page']['base'],
                "url" => $meta['page']['url'] ?? $url,
                "title" => $meta['page']['title'] ?? $const['page']['title'],
                "desc" => $meta['page']['desc'] ?? $const['page']['desc'],
                "img" => $meta['page']['img'] ?? $const['page']['img'],
                "author" => $meta['page']['author'] ?? $const['page']['author'],
                // This is required to split the views or templates into front (Landing page and the likes) and back (Dashboard and the likes)
                "type" =>  $meta['page']['type'] ?? $const['page']['type'],
                // This naturally is used to get the root directory of the current script accessing the view painter
            ],
            "body" =>  [
                "class" =>  $meta['body']['class'] ?? $const['body']['class'],
                "attr" =>   $meta['body']['attr'] ?? $const['body']['attr'],
            ],
            // view can be string, which is a location to the view or
            // A void Closure that takes the $meta array param as arg 1
            // and ...$meta_args from the paint function as other args
            // ex. ... 'head' => function($meta,...$meta_args) : void {}
            // by being void, it means the output should be echoed not returned
            // and when it is being used after being processed, it should be echoed as well
            // this is because the void function is being echoed to an ob_string buffer
            "view" => [
                "head" => $meta['view']['head'] ?? null,
                "body" => $meta['view']['body'] ?? null,
                "script" => $meta['view']['script'] ?? null,
            ],
            // This searches for assets on the client/{env}/custom folder
            "src" => [
                "js" => $meta['src']['js'] ?? [],
                "css" => $meta['src']['css'] ?? [],
                "plugin" => $meta['src']['plugin'] ?? [],
            ],
            // This searches for assets on the client/{env}/{front||back}
            // {front||back} is based on the $meta['page']['type']. Default is front
            "dist" => [
                "js" => $meta['dist']['js'] ?? [],
                "css" => $meta['dist']['css'] ?? [],
                "root" => [
                    "css" => $meta['dist']['root']['css'] ?? [],
                    "js" => $meta['dist']['root']['js'] ?? [],
                ],
            ],
            /**
             * When `assets` is in use, `src` and `dist` are both disabled, this is because, assets is a combination of the both of them;
             * `assets` searches for assets based on the `ARRAY_KEY`/`DIRECTORY_NAME`
             * When using `assets`, it is required that you add the file extension of each asset, unlike its predecessor
             * @example
                 "assets" => [
                    "custom" => [
                        "js/front/contact-us.js"
                    ],
                ],
             */
            "assets" => [
                "custom" => $meta['assets']['custom'] ?? [],
                "front" => $meta['assets']['front'] ?? [],
                "back" => $meta['assets']['back'] ?? [],
            ],
            "local" => $meta['local'] ?? [],
            "local_raw" => $meta['local_raw'] ?? [],
        ];

        $layConfig = LayConfig::instance();
        $name = $layConfig->get_site_data('name');

        $meta['page']['title_raw'] = $meta['page']['title'];
        $meta['page']['title'] =
            strtolower($meta['page']['title_raw']) == "homepage" ? $name->full :
            $meta['page']['title_raw'] . " :: " . $name->short;

        // pass the variables required by include files from this scope to their scope. This affects all files included within this same scope
        $layConfig::set_inc_vars([
            "META" => $meta,
            "LOCAL" => $meta['local'],
            "LOCAL_RAW" => $meta['local_raw'],
        ]);

        self::$VIEW_ARGS = [...$meta_args];
        $this->skeleton($meta);
    }

    public static function constants(array $meta) : void {
        $const = self::$constant_attributes ?? [];
        self::$constant_attributes = [
            "core" => [
                "close_connection" => $meta['core']['close_connection'] ?? $const['core']['close_connection'] ?? true,
                "script" => $meta['core']['script'] ?? $const['core']['script'] ?? true,
                "strict" => $meta['core']['strict'] ?? $const['core']['strict'] ?? true,
                "skeleton" => $meta['core']['skeleton'] ?? $const['core']['skeleton'] ?? true,
            ],
            "body" =>  [
                "class" => $meta['body']['class'] ?? $const['body']['class'] ?? "",
                "attr" => $meta['body']['attr'] ?? $const['body']['attr'] ?? "",
            ],
            "page" => [
                "charset" =>  $meta['page']['charset'] ?? $const['page']['charset'] ?? "UTF-8",
                "base" =>  $meta['page']['base'] ?? $const['page']['base'] ?? null,
                "title" =>  $meta['page']['title'] ?? $const['page']['title'] ?? null,
                "desc" =>   $meta['page']['desc'] ?? $const['page']['desc'] ?? null,
                "type" =>   $meta['page']['type'] ?? $const['page']['type'] ?? null,
                "img" => $meta['page']['img'] ?? $const['page']['img'] ?? null,
                "author" => $meta['page']['author'] ?? $const['page']['author'] ?? null,
            ]
        ];
    }

    private function skeleton(array $meta) : void {
        $layConfig = LayConfig::instance();
        $site_data = $layConfig->get_site_data();
        $client = $layConfig->get_res__client();
        $page = $meta['page'];

        $img = $page['img'] ?? $site_data->img->icon;
        $author = $page['author'] ?? $site_data->author;
        $title = $page['title'];
        $title_raw = $page['title_raw'];
        $base = $page['base'] ?? $site_data->base;
        $charset = $page['charset'];
        $desc = $page['desc'];
        $color = $site_data->color->pry;

        $page = <<<STR
        <!DOCTYPE html>
        <html itemscope lang="en" id="LAY-HTML">
        <head>
            <title id="LAY-PAGE-TITLE-FULL">$title</title>
            <base href="$base" id="LAY-PAGE-BASE">
            <meta http-equiv="content-type" content="text/html;charset=$charset" />
            <meta name="description" id="LAY-PAGE-DESC" content="$desc">
            <meta name="author" content="<?php echo $author ?>">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, shrink-to-fit=no">
            <meta name="theme-color" content="$color">
            <meta name="msapplication-navbutton-color" content="$color">
            <meta name="msapplication-tap-highlight" content="no">
            <meta name="apple-mobile-web-app-capable" content="yes">
            <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
            <!-- Framework Tags-->
            <meta property="lay:page_type" id="LAY-PAGE-TYPE" content="{$page['type']}">
            <meta property="lay:site_name_short" id="LAY-SITE-NAME-SHORT" content="{$site_data->name->short}">
            <!-- // Framework Tags-->
            <meta property="og:title" id="LAY-PAGE-TITLE" content="$title_raw">
            <meta property="og:url" id="LAY-PAGE-URL" content="{$page['url']}">
            <meta property="og:type" content="website">
            <meta property="og:site_name" id="LAY-SITE-NAME" content="{$site_data->name->full}">
            <meta property="og:description" content="{$page['desc']}">
            <meta property="og:image" content="$img">
            <meta itemprop="name" content="$title">
            <meta itemprop="description" content="{$page['desc']}">
            <meta itemprop="image" id="LAY-PAGE-IMG" content="{$img}">
            <link rel="icon" type="image/x-icon" href="{$site_data->img->favicon}">
            {$this->skeleton_head($meta)}
        </head>
        <body class="{$meta['body']['class']}" {$meta['body']['attr']}>
            <!--//START LAY CONSTANTS-->
            <input type="hidden" id="LAY-API" value="$client->api">
            <input type="hidden" id="LAY-UPLOAD" value="$client->upload">
            <input type="hidden" id="LAY-CUSTOM-IMG" value="{$client->custom->img}">
            <input type="hidden" id="LAY-BACK-IMG" value="{$client->back->img}">
            <input type="hidden" id="LAY-FRONT-IMG" value="{$client->front->img}">
            <!--//END LAY CONSTANTS-->
            {$this->skeleton_body($meta)}{$this->skeleton_script($meta)}
        </body></html>
        STR;
        
        if($layConfig::is_page_compressed())
            $page = preg_replace("/>(\s)+</m","><",preg_replace("[<!--(?!<!)[^\[>].*?-->]","",$page));

        echo $page;
    }

    # This uses the parameters passed from the page array to handle the view either as Closure or by inclusion
    private function view_handler(string $view_section, array &$meta) : string {
        $meta_view = $meta['view'][$view_section];
        $layConfig = LayConfig::instance();
        $inc_type = $meta['page']['type'] == "back" ? "inc_back" : "inc_front";
        $type = "front";
        $section_prefix = $view_section == "body" ? "view" : "inc";

        if($meta['page']['type'] == "back")
            $type = "back";

        ob_start();
            if($meta_view instanceof Closure)
                $meta_view($meta, ...self::$VIEW_ARGS);
            elseif($meta_view)
                $layConfig->inc_file($meta_view, $section_prefix . "_" . $type, true, $meta['core']['strict']);
        $meta_view = ob_get_clean();

        if($meta['core']['skeleton'] === true)
            return $layConfig->inc_file($view_section, $inc_type,true,$meta['core']['strict'],[
                "INCLUDE_AS_STRING" => true,
                "META" => [
                    "view" => [
                        $view_section => $meta_view
                    ]
                ]
            ]);

        return $meta_view;
    }

    # <Body> including <Header> or Top Half of <Body>
    private function skeleton_body(array $meta) : string {
        return $this->view_handler('body',$meta);
    }

    # <Head> values that belong inside the <head> tag
    private function skeleton_head(array &$meta) : string {
        $layConfig = LayConfig::instance();
        $client = $layConfig->get_res__client();
        $view = $this->view_handler('head',$meta);
        $using_assets = false;

        $section = $meta['page']['type'] == "back" ? $client->back : $client->front;
        $custom_css = $client->custom->css;
        $plugin = $client->custom->plugin;
        $check_css_args = function ($file) : array{
            if(is_array($file)){
                $media = $file['media'];
                $rel = $file['rel'];
                $type = $file['type'];
                $file = $file[0];
            }
            else {
                $media = "all";
                $rel = "stylesheet";
                $type = "text/css";
            }
            return [$media,$rel,$type,$file];
        };
        $add_asset = function (array &$entry, string &$view, string $res) use($check_css_args) : void {
            foreach ($entry as $k => $e) {
                $a = $check_css_args($e);
                $f = trim($a[3]);
                if (count(explode(".css", $f,2)) > 1) {
                    $f = $res . $f;
                    $view .= '<link href="' . $f . '" type="' . $a[2] . '" rel="' . $a[1] . '" media="' . $a[0] . '" />';
                    unset($entry[$k]);
                }
            }
        };

        foreach ($meta['assets'] as $k => $f) {
            switch ($k){
                default: $res = $client->custom->root; break;
                case "front": $res = $client->front->root; break;
                case "back": $res = $client->back->root; break;
            };

            $add_asset($f,$view,$res);
            $using_assets = true;
        }

        if($using_assets)
            return $view;

        foreach ($meta['dist']['root']['css'] as $f) {
            $a = $check_css_args($f);
            $f = $section->root . explode(".css", $a[3])[0] . ".css";
            $view .= '<link href="' . $f . '" type="' . $a[2] . '" rel="' . $a[1] . '" media="' . $a[0] . '" />';
        }
        foreach ($meta['dist']['css'] as $f) {
            $a = $check_css_args($f);
            $f = $section->css . explode(".css", $a[0])[0] . ".css";
            $view .= '<link href="' . $f . '" type="' . $a[2] . '" rel="' . $a[1] . '" media="' . $a[0] . '" />';
        }
        foreach ($meta['src']['plugin'] as $k => $f) {
            $a = $check_css_args($f);
            $f = explode(".css", $a[3]);
            if (count($f) > 1) {
                $f = $plugin . $f[0] . ".css";
                $view .= '<link href="' . $f . '" type="' . $a[2] . '" rel="' . $a[1] . '" media="' . $a[0] . '" />';
                unset($meta['src']['plugin'][$k]);
            }
        }
        foreach ($meta['src']['css'] as $f) {
            $a = $check_css_args($f);
            $f = $custom_css . explode(".css", $a[3])[0] . ".css";
            $view .= '<link href="' . $f . '" type="' . $a[2] . '" rel="' . $a[1] . '" media="' . $a[0] . '" />';
        }
        return $view;
    }

    # <Script Tags go here>
    private function skeleton_script(array $meta) : string {
        $layConfig = LayConfig::instance();
        $s = DIRECTORY_SEPARATOR;
        $env = strtolower($layConfig::get_env());
        $client = $layConfig->get_res__client();

        $core_script = "";
        $lay_root = $layConfig->get_res__server("dir") . $s . "Lay" . $s;
        $lay_base = $client->lay;
        $using_assets = false;

        if($meta['core']['script']) {
            if ($env == "prod") {
                if (file_exists($lay_root . 'omj$' . $s . 'index.min.js'))
                    $core_script .= '<script src="' . $lay_base . 'omj$/index.min.js"></script>';
                if (file_exists($lay_root . "static{$s}js{$s}constants.min.js"))
                    $core_script .= '<script src="' . $lay_base . 'static/js/constants.min.js"></script>';
            } else {
                $core_script .= '<script src="' . $lay_base . 'omj$/index.js"></script>';
                $core_script .= '<script src="' . $lay_base . 'static/js/constants.js"></script>';
            }
        }

        $validate_file = function ($file) : void {
            if(is_array($file))
                Exception::throw_exception(
                "You are trying to use an array to include a script and it's not allowed",
                "SCRIPT::INC::ERR");
        };
        $view = $this->view_handler('script',$meta);
        $custom_js = $client->custom->js;
        $plugin = $client->custom->plugin;
        $section = $meta['page']['type'] == "back" ? $client->back : $client->front;

        $add_asset = function (array &$entry, string &$view, string $res, &$using_assets) use($validate_file) : void {
            foreach ($entry as $k => $f) {
                $validate_file($f);
                $f = trim($f);
                if (count(explode(".js", $f,2)) > 1) {
                    $f = $res . $f;
                    $view .= '<script src="' . $f . '"></script>';
                    unset($entry[$k]);
                    $using_assets = true;
                }
            }
        };

        foreach ($meta['assets'] as $k => $f) {
            switch ($k){
                default: $res = $client->custom->root; break;
                case "front": $res = $client->front->root; break;
                case "back": $res = $client->back->root; break;
            };

            $add_asset($f,$view,$res,$using_assets);
        }

        if($using_assets) {
            if($meta['core']['close_connection']) $layConfig->close_sql();
            return $core_script . $view;
        }

        foreach ($meta['dist']['root']['js'] as $f) {
            $validate_file($f);
            $f = $section->root . explode(".js",$f)[0] . ".js";
            $view .= '<script src="' . $f . '"></script>';
        }
        foreach ($meta['dist']['js'] as $f) {
            $validate_file($f);
            $f = $section->js . explode(".js",$f)[0] . ".js";
            $view .= '<script src="' . $f . '"></script>';
        }
        foreach ($meta['src']['plugin'] as $k => $f) {
            $validate_file($f);
            $f = explode(".js", $f);
            if (count($f) > 1) {
                $f = $plugin . $f[0] . ".js";
                $view .= '<script src="' . $f . '"></script>';
                unset($meta['src']['plugin'][$k]);
            }
        }
        foreach ($meta['src']['js'] as $f) {
            $validate_file($f);
            $f = $custom_js . explode(".js",$f)[0] . ".js";
            $view .= '<script src="' . $f . '"></script>';
        }

        if($meta['core']['close_connection']) $layConfig->close_sql();
        return $core_script . $view;
    }
}