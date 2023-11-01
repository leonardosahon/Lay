<?php
declare(strict_types=1);
namespace Lay\core\view\tags;

use JetBrains\PhpStorm\ExpectedValues;
use Lay\core\LayConfig;
use Lay\core\view\enums\DomainType;
use Lay\core\view\ViewBuilder;
use Lay\core\view\ViewDomain;
use Lay\core\view\ViewSrc;

final class Img {
    private string $attr = "";
    private string $alt = "Page Image";
    private int|string $width;
    private int|string $height;

    public static function new() : self {
        return new self();
    }

    public function attr(string $attr) : self {
        $this->attr .= " " . $attr;
        return $this;
    }

    public function class(string $class_name) : self {
        return $this->attr('class=" ' . $class_name . '"');
    }

    public function width(int|string $width) : self {
        $this->width = $width;
        return $this;
    }
    public function height(int|string $height) : self {
        $this->height = $height;
        return $this;
    }

    public function ratio(int|string $width, int|string $height) : self {
        $this->width($width);
        $this->height($height);
        return $this;
    }

    public function alt(string $alt_text) : self {
        $this->alt = $alt_text;
        return $this;
    }

    public function src(string $src, bool $lazy_load = true) : string {
        $src = ViewSrc::gen($src);
        $lazy_load = $lazy_load ? 'lazy' : 'eager';
        $this->width = $this->width ?? 'auto';
        $this->height = $this->height ?? 'auto';
        $width = @$this->width == "auto" ? '' : "width='$this->width'";
        $height = @$this->height == "auto" ? '' : "height='$this->height'";

        return <<<LNK
            <img src="$src" alt="{$this->alt}" loading="$lazy_load" $width $height {$this->attr} />
        LNK;
    }

}
