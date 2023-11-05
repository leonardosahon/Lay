<?php
declare(strict_types=1);
namespace Lay\core\view\tags;

use Lay\core\LayConfig;
use Lay\core\view\enums\DomainType;
use Lay\core\view\ViewBuilder;
use Lay\core\view\ViewDomain;
use Lay\core\view\ViewSrc;

final class Img {
    private const ATTRIBUTES = [
        "alt" => "Page Image"
    ];

    use \Lay\core\view\tags\traits\Standard;

    public function class(string $class_name) : self {
        return $this->attr('class', $class_name);
    }

    public function width(int|string $width) : self {
        return $this->attr('width', $width);
    }
    
    public function height(int|string $height) : self {
        return $this->attr('height', $height);
    }

    public function ratio(int|string $width, int|string $height) : self {
        $this->width($width);
        $this->height($height);
        return $this;
    }

    public function alt(string $alt_text) : self {
        return $this->attr('alt', $alt_text);
    }

    public function src(string $src, bool $lazy_load = true) : string {
        $src = ViewSrc::gen($src);
        $lazy_load = $lazy_load ? 'lazy' : 'eager';
        $attr = $this->get_attr();

        return <<<LNK
            <img src="$src" loading="$lazy_load" $attr />
        LNK;
    }

}
