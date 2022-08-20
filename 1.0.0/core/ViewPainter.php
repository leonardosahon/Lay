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

    public function paint(array &$page, ...$others) : void {
        if(empty(self::$constant_attributes))
            self::constants([]);

        $url = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : $_SERVER['REQUEST_URI'];
        $const = self::$constant_attributes;

        $page = [
            "core" => [
                "close_connection" => $page['core']['close_connection'] ?? $const['core']['close_connection'],
                "script" => $page['core']['script'] ?? $const['core']['script'],
                "strict" => $page['core']['strict'] ?? $const['core']['strict'],
                "skeleton" => $page['core']['skeleton'] ?? $const['core']['skeleton'],
            ],
            "page" => [
                "charset" =>  $page['page']['charset'] ?? $const['page']['charset'],
                "base" =>  $page['page']['base'] ?? $const['page']['base'],
                "url" => $page['page']['url'] ?? $url,
                "title" => $page['page']['title'] ?? $const['page']['title'],
                "desc" => $page['page']['desc'] ?? $const['page']['desc'],
                "img" => $page['page']['img'] ?? $const['page']['img'],
                "author" => $page['page']['author'] ?? $const['page']['author'],
                // This is required to split the views or templates into front (Landing page and the likes) and back (Dashboard and the likes)
                "type" =>  $page['page']['type'] ?? $const['page']['type'],
                // This naturally is used to get the root directory of the current script accessing the view painter
                // ex. `admin/` for a script inside the admin folder like index_closure.php
                "root" => $page['page']['root'] ?? $const['page']['root'],
                // for situations where the page crumbs is to be mapped out
                "crumbs" => $page["crumbs"] ?? [],
            ],
            "body" =>  [
                "class" =>  $page['body']['class'] ?? $const['body']['class'],
                "attr" =>   $page['body']['attr'] ?? $const['body']['attr'],
            ],
            // view can be string, which is a location to the view or
            // A void Closure that takes the $page array param as arg 1
            // and ...$others from the paint function as other args
            // ex. ... 'head' => function($page,...$others) : void {}
            // by being void, it means the output should be echoed not returned
            // and when it is being used after being processed, it should be echoed as well
            // this is because the void function is being echoed to an ob_string buffer
            "view" => [
                "head" => $page['view']['head'] ?? null,
                "body" => $page['view']['body'] ?? null,
                "script" => $page['view']['script'] ?? null,
            ],
            // This searches for assets on the client/{env}/custom folder
            "src" => [
                "js" => $page['src']['js'] ?? [],
                "css" => $page['src']['css'] ?? [],
                "plugin" => $page['src']['plugin'] ?? [],
            ],
            // This searches for assets on the client/{env}/{front||back}
            // {front||back} is based on the $page['page']['type']. Default is front
            "dist" => [
                "js" => $page['dist']['js'] ?? [],
                "css" => $page['dist']['css'] ?? [],
                "root" => [
                    "css" => $page['dist']['root']['css'] ?? [],
                    "js" => $page['dist']['root']['js'] ?? [],
                ],
            ],
            "local" => $page['local'] ?? [],
            "local_raw" => $page['local_raw'] ?? [],
        ];

        $page['page']['title_raw'] = $page['page']['title'];
        $layConfig = LayConfig::instance();
        $name_full = $layConfig->get_site_data('name','full');
        $page['page']['title'] = strtolower($page['page']['title_raw']) == "homepage" ? $name_full :  $page['page']['title_raw'] . " :: " . $name_full;

        // pass the variables required by include files from this scope to their scope. This affects all files included within this same scope
        layConfig::instance()::set_inc_vars([
            "META" => $page,
            "LOCAL" => $page['local'],
            "LOCAL_RAW" => $page['local_raw'],
        ]);

        self::$VIEW_ARGS = [...$others];
        $this->skeleton($page);
    }

    public static function constants(array $page) : void {
        $const = self::$constant_attributes ?? [];
        self::$constant_attributes = [
            "core" => [
                "close_connection" => $page['core']['close_connection'] ?? $const['core']['close_connection'] ?? true,
                "script" => $page['core']['script'] ?? $const['core']['script'] ?? true,
                "strict" => $page['core']['strict'] ?? $const['core']['strict'] ?? true,
                "skeleton" => $page['core']['skeleton'] ?? $const['core']['skeleton'] ?? true,
            ],
            "body" =>  [
                "class" => $page['body']['class'] ?? $const['body']['class'] ?? "",
                "attr" => $page['body']['attr'] ?? $const['body']['attr'] ?? "",
            ],
            "page" => [
                "charset" =>  $page['page']['charset'] ?? $const['page']['charset'] ?? "UTF-8",
                "base" =>  $page['page']['base'] ?? $const['page']['base'] ?? null,
                "title" =>  $page['page']['title'] ?? $const['page']['title'] ?? null,
                "desc" =>   $page['page']['desc'] ?? $const['page']['desc'] ?? null,
                "type" =>   $page['page']['type'] ?? $const['page']['type'] ?? null,
                "root" =>   $page['page']['root'] ?? $const['page']['root'] ?? null,
                "img" => $page['page']['img'] ?? $const['page']['img'] ?? null,
                "author" => $page['page']['author'] ?? $const['page']['author'] ?? null,
            ]
        ];
    }

    private function skeleton(array $page) : void {
        $layConfig = LayConfig::instance();
        $img = $page['page']['img'] ?? $layConfig->get_site_data('img','icon');
        $author = $page['page']['author'] ?? $layConfig->get_site_data('author');
        $title = $page['page']['title'];
        $base = $page['page']['base'] ?? $layConfig->get_site_data('base');
        $charset = $page['page']['charset'];
        ?>
        <!DOCTYPE html>
        <html lang="en" class="no-js">
        <head>
            <title id="LAY-PAGE-TITLE-FULL"><?php echo $title ?></title>
            <base href="<?php echo $base ?>" id="LAY-PAGE-BASE">
            <meta charset="<?php echo $charset ?>">
            <meta http-equiv="content-type" content="text/html;charset=<?php echo $charset ?>" />
            <meta name="description" id="LAY-PAGE-DESC" content="<?php echo $page['page']['desc'] ?>">
            <meta name="author" content="<?php echo $author ?>">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5, shrink-to-fit=no">
            <meta name="theme-color" content="<?php echo $layConfig->get_site_data('color','pry') ?>">
            <meta name="msapplication-navbutton-color" content="<?php echo $layConfig->get_site_data('color','pry') ?>">
            <meta name="msapplication-tap-highlight" content="no">
            <meta name="apple-mobile-web-app-capable" content="yes">
            <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
            <!-- Framework Tags-->
            <meta property="lay:page_type" id="LAY-PAGE-TYPE" content="<?php echo $page['page']['type'] ?>">
            <meta property="lay:page_root" id="LAY-PAGE-ROOT" content="<?php echo $page['page']['root'] ?>">
            <!-- // Framework Tags-->
            <meta property="og:title" id="LAY-PAGE-TITLE" content="<?php echo $title ?>">
            <meta property="og:url" id="LAY-PAGE-URL" content="<?php echo $page['page']['url'] ?>">
            <meta property="og:type" content="website">
            <meta property="og:site_name" id="LAY-SITE-NAME" content="<?php echo $layConfig->get_site_data('name','full') ?>">
            <meta property="og:site_name_short" id="LAY-SITE-NAME-SHORT" content="<?php echo $layConfig->get_site_data('name','short') ?>">
            <meta property="og:description" content="<?php echo $page['page']['desc'] ?>">
            <meta property="og:image" content="<?php echo $img ?>">
            <meta itemprop="name" content="<?php echo $title ?>">
            <meta itemprop="description" content="<?php echo $page['page']['desc'] ?>">
            <meta itemprop="image" id="LAY-PAGE-IMG" content="<?php echo $img ?>">
            <link rel="icon" type="image/x-icon" href="<?php echo $layConfig->get_site_data('img','favicon') ?>">
            <?php $this->skeleton_head($page); ?>
        </head>
        <body class="<?php echo $page['body']['class'] ?>" <?php echo $page['body']['attr'] ?>>
            <!--//START LAY CONSTANTS-->
            <input type="hidden" id="LAY-API" value="<?php echo $layConfig->get_res__client("api") ?>">
            <input type="hidden" id="LAY-UPLOAD" value="<?php echo $layConfig->get_res__client("upload") ?>">
            <input type="hidden" id="LAY-CUSTOM-IMG" value="<?php echo $layConfig->get_res__client("custom","img") ?>">
            <input type="hidden" id="LAY-BACK-IMG" value="<?php echo $layConfig->get_res__client("back","img") ?>">
            <input type="hidden" id="LAY-FRONT-IMG" value="<?php echo $layConfig->get_res__client("front","img") ?>">
            <!--//END LAY CONSTANTS-->
            <?php $this->skeleton_body($page);
            $this->skeleton_script($page); ?>
        </body></html><?php
    }

    # This uses the parameters passed from the page array to handle the view either as Closure or by inclusion
    private function view_handler(string $view_section, array &$page) : string {
        $page_view = $page['view'][$view_section];
        $layConfig = LayConfig::instance();
        $type = "front";
        $section_prefix = $view_section == "body" ? "view" : "inc";

        if($page['page']['type'] == "back")
            $type = "back";

        #### START
        ob_start();
        if($page_view instanceof Closure)
            $page_view($page, ...self::$VIEW_ARGS);
        elseif($page_view)
            $layConfig->inc_file($page_view, $section_prefix . "_" . $type, true, $page['core']['strict']);

        return ob_get_clean();
        #### END
    }

    # <Head> values that belong inside the <head> tag
    private function skeleton_head(array $page) : void {
        $layConfig = LayConfig::instance();
        $type = "inc_front";
        $section = $layConfig->get_res__client('front');
        $view = $this->view_handler('head',$page);
        $custom_css = $layConfig->get_res__client("custom","css");
        $plugin = $layConfig->get_res__client("custom","plugin");

        if($page['page']['type'] == "back") {
            $type = "inc_back";
            $section = $layConfig->get_res__client('back');
        }

        if($page['core']['skeleton'] === true)
            $layConfig->inc_file("head",$type,true,$page['core']['strict'],[
                "META" => [
                    "view" => [
                        "head" => $view
                    ]
                ]
            ]);
        else
            echo $view;

        foreach ($page['dist']['root']['css'] as $css): ?><link href="<?php echo $section->root . explode(".css",$css)[0] ?>.css" type="text/css" rel="stylesheet" media="all" /><?php endforeach;
        foreach ($page['dist']['css'] as $css): ?><link href="<?php echo $section->css . explode(".css",$css)[0] ?>.css" type="text/css" rel="stylesheet" media="all" /><?php endforeach;
        foreach ($page['src']['plugin'] as $p):
            if(!$p) continue;
            $ext = explode(".css",$p);
            if(count($ext) > 1) : ?><link href="<?php echo $plugin . $ext[0] ?>.css" type="text/css" rel="stylesheet" media="all" /><?php endif;
        endforeach;
        foreach ($page['src']['css'] as $css): ?> <link href="<?php echo $custom_css . explode(".css",$css)[0] ?>.css" type="text/css" rel="stylesheet" media="all" /><?php endforeach;
    }

    # <Body> including <Header> or Top Half of <Body>
    private function skeleton_body(array $page) : void {
        $layConfig = LayConfig::instance();
        $type = "inc_front";
        $view = $this->view_handler('body',$page);

        if($page['page']['type'] == "back")
            $type = "inc_back";

        if($page['core']['skeleton'] === true)
            $layConfig->inc_file("body",$type,true,$page['core']['strict'],[
                "META" => [
                    "view" => [
                        "body" => $view
                    ]
                ]
            ]);
        else
            echo $view;
    }

    # <Script Tags go here>
    private function skeleton_script(array $page) : void {
        $layConfig = LayConfig::instance();
        $env = strtolower($layConfig::get_env());
        $view = $this->view_handler('script',$page);
        $root = $layConfig->get_res__server("dir");
        $base = $layConfig->get_res__client("lay");
        $custom_js = $layConfig->get_res__client("custom","js");
        $plugin = $layConfig->get_res__client("custom","plugin");
        $type = "inc_back";
        $section = $layConfig->get_res__client("back");

        if($page['core']['script']):
            if($env == "prod" && file_exists($root . "Lay/omj$/index.min.js")): ?>
                <script src="<?php echo $base ?>omj$/index.min.js"></script>
            <?php else : ?>
                <script src="<?php echo $base ?>omj$/index.js"></script>
            <?php endif;
            if($env == "prod" && file_exists($root . "Lay/static/js/constants.min.js")): ?>
                <script src="<?php echo $base ?>static/js/constants.min.js"></script>
            <?php else : ?>
                <script src="<?php echo $base ?>static/js/constants.js"></script>
            <?php endif;
        endif;

        if($page['page']['type'] == "front") {
            $type = "inc_front";
            $section = $layConfig->get_res__client("front");
        }

        if($page['core']['skeleton'] === true)
            $layConfig->inc_file("script",$type,true,$page['core']['strict'],[
                "META" => [
                    "view" => [
                        "script" => $view
                    ]
                ]
            ]);
        else
            echo $view;

        foreach ($page['dist']['root']['js'] as $script): ?> <script src="<?php echo $section->root . explode(".js",$script)[0] ?>.js"></script> <?php endforeach;
        foreach ($page['dist']['js'] as $script): ?> <script src="<?php echo $section->js . explode(".js",$script)[0] ?>.js"></script> <?php endforeach;
        foreach ($page['src']['plugin'] as $p):
            if(!$p) continue;
            $ext = explode(".js",$p);
            if(count($ext) > 1) : ?><script src="<?php echo $plugin . $ext[0] ?>.js"></script><?php endif;
        endforeach;
        foreach ($page['src']['js'] as $script): ?> <script src="<?php echo $custom_js . explode(".js",$script)[0] ?>.js"></script> <?php endforeach;

        if($page['core']['close_connection']) $layConfig->close_sql();
    }
}