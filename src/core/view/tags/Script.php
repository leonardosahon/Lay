<?php
declare(strict_types=1);
namespace Lay\core\view\tags;

use Lay\core\LayConfig;
use Lay\core\view\ViewSrc;

final class Script {
    private const ATTRIBUTES = [
        "defer" => "true",
        "type" => "text/javascript",
    ];
    
    use \Lay\core\view\tags\traits\Standard;

    public function type(string $type) : self {
        return $this->attr('type', $type);
    }
    
    public function defer(bool $choice) : self {
        return $this->attr('defer', (string) $choice);
    }
    
    public function async(bool $choice) : self {
        return $this->attr('async', (string) $choice);
    }

    public function src(string $src, bool $print = true) : string {
        $src = ViewSrc::gen($src);
        $attr = $this->get_attr();

        $link = <<<LNK
            <script src="$src" $attr></script>
        LNK;

        if($print)
            echo $link;

        return $link;
    }

}
