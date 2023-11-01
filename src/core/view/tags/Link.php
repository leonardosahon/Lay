<?php
declare(strict_types=1);
namespace Lay\core\view\tags;

use Lay\core\view\ViewSrc;

final class Link {

    private string $rel = "stylesheet";
    private string $media = "all";
    private string $attr = "";
    private string $type = "";

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

    public function href(string $href, bool $print = true) : string {
        $href = ViewSrc::gen($href);
        $type = !empty($this->type) ? $this->type : "text/css";

        $link = <<<LNK
            <link href="$href" rel="$this->rel" $this->attr media="$this->media" type="$type" />
        LNK;

        if($print)
            echo $link;

        return $link;
    }

}
