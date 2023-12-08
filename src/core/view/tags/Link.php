<?php
declare(strict_types=1);
namespace Lay\core\view\tags;

use Lay\core\view\ViewSrc;

final class Link {
    private bool $rel_set = false;
    private const ATTRIBUTES = [
        "rel" => "stylesheet",
        "media" => "all",
        "type" => "text/css",
    ];
    
    use \Lay\core\view\tags\traits\Standard;
    
    public function rel(string $rel) : self {
        $this->rel_set = true;
        return $this->attr('rel', $rel);
    }

    public static function clear() : void {
        self::$me->rel_set = false;
        self::$me->attr = self::ATTRIBUTES ?? [];
    }

    public function media(string $media) : self {
        return $this->attr('media', $media);
    }

    public function type(string $type) : self {
        return $this->attr('type', $type);
    }

    public function href(string $href, bool $print = true, bool $lazy = false) : string {
        $href = ViewSrc::gen($href);

        if(!$this->rel_set)
            $this->rel("stylesheet");

        $media = $this->attr['media'] ?? "all";
        $rel = $this->attr['rel'] ?? "stylesheet";
        $type = $this->attr['type'] ?? "text/css";
        $attr = $this->get_attr();

        $link = "<link href=\"$href\" $attr />";

        if($lazy) {
            $attr = <<<ATTR
            media="print" onload="this.media='$media'" rel="$rel" href="$href" type="$type" $attr 
            ATTR;

            $link = "<link $attr />";
        }

        if($print)
            echo $link;

        return $link;
    }

}
