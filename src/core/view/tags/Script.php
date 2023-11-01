<?php
declare(strict_types=1);
namespace Lay\core\view\tags;

use Lay\core\LayConfig;
use Lay\core\view\ViewSrc;

final class Script {
    private bool $defer = true;
    private bool $async = false;
    private string $attr = "";
    private string $type = "";

    public static function new() : self {
        return new self();
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

    public function src(string $src, bool $print = true) : string {
        $src = ViewSrc::gen($src);

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
