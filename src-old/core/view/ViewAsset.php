<?php
declare(strict_types=1);
namespace Lay\core\view;

use Lay\core\LayConfig;

final class ViewAsset {

    private string $rel = "stylesheet";
    private string $media = "all";
    private bool $defer = true;
    private bool $async = false;
    private string $attr = "";
    private string $type = "";
    private static object $client_resource;

    public function __construct() {
        self::$client_resource = LayConfig::res_client();
    }
    private function form_src(string $src) : string {
        $base = LayConfig::site_data()->base;

        $src = str_replace(
            [ "@front/", "@back/", "@custom/" ],
            [ self::$client_resource->front->root, self::$client_resource->back->root, self::$client_resource->custom->root ],
            $src
        );

        if(!str_starts_with($src, $base))
            return $src;

        $local_file = str_replace($base, "", $src);

        if(file_exists($local_file))
            $src .= "?mt=" . filemtime($local_file);

        return $src;
    }

    public static function new() : self {
        return new self();
    }

    public function rel(string $rel) : self {
        $this->rel = $rel;
        return $this;
    }

    public function media(string $media) : self {
        $this->media = $media;
        return $this;
    }

    public function attr(string $attr) : self {
        $this->attr = $attr;
        return $this;
    }

    public function type(string $type) : self {
        $this->type = $type;
        return $this;
    }

    public function defer(bool $defer) : self {
        $this->defer = $defer;
        return $this;
    }

    public function async(bool $async) : self {
        $this->async = $async;
        return $this;
    }

    public function link(string $href, bool $print = true) : string {
        $href = $this->form_src($href);
        $type = !empty($this->type) ? $this->type : "text/css";

        $link = <<<LNK
            <link href="$href" rel="$this->rel" $this->attr media="$this->media" type="$type" />
        LNK;

        if($print)
            echo $link;

        return $link;
    }

    public function script(string $src, bool $print = true) : string {
        $src = $this->form_src($src);

        $defer = $this->defer ? "defer" : "";
        $async = $this->async ? "async" : "";
        $type = !empty($this->type) ? $this->type : "text/javascript";

        $link = <<<LNK
            <script src="$src" $defer $async $this->attr type="$type"></script>
        LNK;

        if($print)
            echo $link;

        return $link;
    }

}
