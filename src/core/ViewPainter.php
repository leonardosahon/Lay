<?php
declare(strict_types=1);
namespace Lay\core;

use Closure;
use Lay\core\sockets\IsSingleton;

/**
 * Page Creator
 */
final class ViewPainter {
    use IsSingleton;
    private static array $constant_attributes = [];

    public static function constants(array $meta) : void {
        $const = array_replace_recursive(self::$constant_attributes, $meta);

        $data = LayConfig::instance()->get_site_data();

        $ser = $data->base_no_proto . "/";
        $repl = $_SERVER['REQUEST_URI'];
        $url = str_replace($ser,$repl,$data->base);

        if($ser == "/")
            $url = rtrim($data->base, "/") . $repl;

        self::$constant_attributes = [
            "core" => [
                "close_connection" => $const['core']['close_connection'] ?? true,
                "script" => $const['core']['script'] ?? true,
                "strict" => $const['core']['strict'] ?? true,
                "skeleton" => $const['core']['skeleton'] ?? true,
            ],
            "page" => [
                "charset" =>  $const['page']['charset'] ?? "UTF-8",
                "base" =>  $const['page']['base'] ?? null,
                "url" => $const['page']['url'] ?? $url,
                "canonical" => $const['page']['canonical'] ?? $url,
                "title" => $const['page']['title'] ?? "Untitled Page",
                "desc" => $const['page']['desc'] ?? "",
                "img" => $const['page']['img'] ?? null,
                "author" => $const['page']['author'] ?? null,
                "append_site_name" => $const['page']['append_site_name'] ?? true,

                // It takes the value {front | back}, this helps ViewPainter locate internal assets matched in folders
                // named __front | __back, for things like: views, includes and controllers
                "type" =>  $const['page']['type'] ?? null,
            ],
            "body" =>  [
                "class" =>  $const['body']['class'] ?? null,
                "attr" =>   $const['body']['attr'] ?? null,
            ],
            /**
             * `view` is an array that accepts three [optional] keys for each section of the html page,
             *     `head` for <link>, <meta> tags or anything you wish to put in the <head>.
             *     `body` for anything that needs to go into the <body> tag, including <script>
             *     `script` is used to explicitly include <script> tags or anything you may wish to add
             *         before the closing of the </body> tag.
             *
             * The keys can be a void Closure that accepts the `$meta` array parameter as its argument.
             *     @example: 'head' => function($meta) : void {echo '<meta name="robots" content="allow" />'; }.
             *
             * The keys can be a string, this string is the location of the file inside the view folder.
             *     The file extension is `.view` when your key is `body`; but `.inc` when it's others.
             *     This means ViewPainter looks for files the value of `body` key inside the view folder,
             *     while it looks for the value of the other keys, inside the includes folder.
             *
             *     `ViewPainter` will look for the files in a folder that matches {__front|__back} depending on the value
             *     of `$meta['page']['type']`.
             *    @example: 'head' => 'header', 'body' => 'homepage',
             **/
            "view" => [
                "head" => $const['view']['head'] ?? null,
                "body" => $const['view']['body'] ?? null,
                "script" => $const['view']['script'] ?? null,
            ],
            /**
             * `assets` searches for assets based on the `ARRAY_KEY`/`DIRECTORY_NAME`
             * @example "assets" => [ "@custom/js/front/contact-us.js", "@front/css/style.css" ].
             * The entries can also be an array:
             * @example "assets" => [ ["src" => "@custom/js/front/contact-us.js", "async" => true, "type" => "text/javascript"], ]
             **/
            "assets" => $const['assets'] ?? [],
            "local" => $const['local'] ?? [],
            "local_raw" => $const['local_raw'] ?? [],
            "local_array" => $const['local_array'] ?? [],
        ];
    }

    public function paint(array $meta_data) : void {
        if(empty(self::$constant_attributes))
            self::constants([]);

        $layConfig = LayConfig::instance();
        $data = $layConfig->get_site_data();

        $const = array_replace_recursive(self::$constant_attributes, $meta_data);;

        $const['page']['title_raw'] = $const['page']['title'];

        if(strtolower($const['page']['title_raw']) == "homepage"){
            $const['page']['title'] = $data->name->full;
            $const['page']['title_raw'] = $data->name->short;
        }
        else{
            $const['page']['title'] = !$const['page']['append_site_name'] ?
                $const['page']['title_raw'] :
                $const['page']['title_raw'] . " :: " . $data->name->short;
        }

        // Pass the variables required by include files from this scope to their scope.
        // This affects all files included within this same scope.
        $layConfig::set_inc_vars([
            "META" => $const,
            "LOCAL" => $const['local'],
            "LOCAL_RAW" => $const['local_raw'],
            "LOCAL_ARRAY" => $const['local_array'],
        ]);

        $this->skeleton($const);
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
        $canonical = <<<LINK
            <link rel="canonical" href="{$page['canonical']}" />
        LINK;

        $page = <<<STR
        <!DOCTYPE html>
        <html itemscope lang="en" id="LAY-HTML">
        <head>
            <title id="LAY-PAGE-TITLE-FULL">$title</title>
            <base href="$base" id="LAY-PAGE-BASE">
            <meta http-equiv="content-type" content="text/html;charset=$charset" />
            <meta name="description" id="LAY-PAGE-DESC" content="$desc">
            <meta name="author" content="$author">
            <meta http-equiv="X-UA-Compatible" content="IE=edge">
            <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
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
            $canonical
            {$this->skeleton_head($meta)}
        </head>
        <body class="{$meta['body']['class']}" {$meta['body']['attr']}>
            <!--//START LAY CONSTANTS-->
            <input type="hidden" id="LAY-API" value="$client->api">
            <input type="hidden" id="LAY-UPLOAD" value="$client->upload">
            <input type="hidden" id="LAY-CUSTOM-IMG" value="{$client->custom->img}">
            <input type="hidden" id="LAY-BACK-IMG" value="{$client->back->img}">
            <input type="hidden" id="LAY-FRONT-IMG" value="{$client->front->img}">
            <input type="hidden" id="LAY-BACK-ROOT" value="{$client->back->root}">
            <input type="hidden" id="LAY-FRONT-ROOT" value="{$client->front->root}">
            <input type="hidden" id="LAY-CUSTOM-ROOT" value="{$client->custom->root}">
            <!--//END LAY CONSTANTS-->
            {$this->skeleton_body($meta)}
            {$this->skeleton_script($meta)}
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
            $meta_view($meta);

        elseif($meta_view)
            $layConfig->inc_file(explode(".$section_prefix", $meta_view)[0], $section_prefix . "_" . $type, strict: $meta['core']['strict']);

        $meta_view = ob_get_clean();

        if($meta['core']['skeleton'] === true)
            return $layConfig->inc_file($view_section, $inc_type, strict: $meta['core']['strict'], vars: [
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

    private function prepare_assets(\Closure $asset_template, array &$assets, string &$view, string $asset_type) : void {
        $client = LayConfig::instance()->get_res__client();

        $resolve_asset = function (string|array &$asset, string|int $assets_key, array &$assets_array, bool $using_root_as_array_key = false) use ($asset_type, $asset_template, $client, &$resolve_asset) : string {
            $filter_src = $using_root_as_array_key ?
                fn (string|null|int $key, string $src) : string  =>
                    match ($key) {
                        default => "",
                        "front" => $client->front->root,
                        "back" => $client->back->root,
                        "custom" => $client->custom->root,
                    } . $src
                : fn (string|null|int $key, string $src) : string  => str_replace(
                    [ "@front/", "@back/", "@custom/" ],
                    [ $client->front->root, $client->back->root, $client->custom->root ],
                    $src
                );

            if(is_string($asset) && !str_ends_with($asset,".$asset_type"))
                return "";

            if (is_array($asset)) {
                if (isset($asset['src'])) {
                    if(!str_ends_with($asset['src'],".$asset_type"))
                        return "";

                    $x = $filter_src($assets_key, $asset['src']);

                    // cleanup the array after adding the asset
                    if(is_int($assets_key))
                        unset($assets_array[$assets_key]);

                    if(empty($x))
                        return "";

                    return $asset_template($x, $asset);
                }

                $added_assets = "";
                // which is "custom" => [,,,], "front" => [,,,]
                // this block is used by the `legacy` way of including assets,
                foreach ($asset as $i => $a) {
                    // cleanup the empty entry
                    if(empty($a)) {
                        if(is_int($i))
                            unset($asset[$i]);

                        continue;
                    }

                    // this is the reason we are passing `$assets_key` and not `$i`
                    $x = $resolve_asset($a, $assets_key, $asset, $using_root_as_array_key);

                    if(empty($x))
                        continue;

                    // cleanup the array entry after adding the asset
                    if(is_int($i))
                        unset($asset[$i]);

                    $added_assets .= $x;
                }

                return $added_assets;
            }

            $x = $filter_src($assets_key, $asset);

            if(empty($x))
                return "";

            // cleanup the array after adding the asset
            if(is_int($assets_key))
                unset($assets_array[$assets_key]);

            return $asset_template($x);
        };


        foreach ($assets as $k => $asset) {
            if(in_array($k, ["custom","front","back"], true)) {
                $view .= $resolve_asset($asset, $k, $assets, true);
                continue;
            }

            $view .= $resolve_asset($asset, $k, $assets, false);
        }
    }
    # <Head> values that belong inside the <head> tag
    private function skeleton_head(array &$meta) : string {
        $css_template = function(string $href, array $attributes = []) : string {
            $rel = $attributes['rel'] ?? "stylesheet";

            if(isset($attributes['rel']))
                unset($attributes['rel']);

            if(isset($attributes['src']))
                unset($attributes['src']);

            $attr = "";
            foreach ($attributes as $i => $a){
                $attr .= "$i=\"$a\" ";
            }

            return <<<LNK
                <link href="$href" rel="$rel" $attr />
            LNK;
        };
        $view = $this->view_handler('head',$meta);

        $this->prepare_assets($css_template, $meta['assets'], $view, "css");

        return $view;
    }

    private function skeleton_script(array $meta) : string {
        $js_template = function (string $src, array $attributes = []) : string {
            $defer = str_replace([1,true], 'true', (string) filter_var($attributes['defer'] ?? true,FILTER_VALIDATE_INT));
            $defer = $defer == '' ? '' : "defer";

            if(isset($attributes['src']))
                unset($attributes['src']);

            if(isset($attributes['defer']))
                unset($attributes['defer']);

            $attr = "";
            foreach ($attributes as $i => $a){
                $attr .= "$i=\"$a\" ";
            }

            return <<<LNK
                <script src="$src" $defer $attr></script>
            LNK;
        };

        $layConfig = LayConfig::instance();
        $core_script = "";

        if($meta['core']['script']) {
            $s = DIRECTORY_SEPARATOR;
            $env = strtolower($layConfig::get_env());
            $lay_root = $layConfig->get_res__server("dir") . $s . "Lay" . $s;
            $lay_base = $layConfig->get_res__client()->lay;
            list($omj,$const) = null;

            if ($env == "prod") {
                if (file_exists($lay_root . 'omj$' . $s . 'index.min.js'))
                    $omj = $js_template($lay_base . 'omj$/index.min.js',['defer' => false]);

                if (file_exists($lay_root . "static{$s}js{$s}constants.min.js"))
                    $const = $js_template($lay_base . 'static/js/constants.min.js',['defer' => false]);
            }

            $core_script .= $omj ?? $js_template($lay_base . 'omj$/index.js',['defer' => false]);
            $core_script .= $const ?? $js_template($lay_base . 'static/js/constants.js',['defer' => false]);
        }

        $view = $this->view_handler('script',$meta);
        $this->prepare_assets($js_template, $meta['assets'], $view, "js");

        if($meta['core']['close_connection'])
            $layConfig->close_sql();

        return $core_script . $view;
    }
}