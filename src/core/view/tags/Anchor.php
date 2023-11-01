<?php
declare(strict_types=1);
namespace Lay\core\view\tags;

use JetBrains\PhpStorm\ExpectedValues;
use Lay\core\LayConfig;
use Lay\core\view\enums\DomainType;
use Lay\core\view\ViewBuilder;
use Lay\core\view\ViewDomain;

final class Anchor {
    private string $attr = "";
    private string $link = "";

    public static function new() : self {
        return new self();
    }

    public function href(?string $link = "", ?string $domain_id = null) : self {
        $req = ViewBuilder::new()->request('*');
        $link = is_null($link) ? '' : $link;
        $base = LayConfig::site_data();
        $base_full = $base->base;

        if(str_starts_with($link,"http")) {
            $base_full = "";
            $domain_id = null;
        }

        if($domain_id) {
            $same_domain = $domain_id == $req['domain_id'];
            $domain_id = ViewDomain::new()->get_domain_by_id($domain_id);

            $req['pattern'] = $domain_id ? $domain_id['patterns'][0] : "*";

            if($req['pattern'] != "*" && LayConfig::$ENV_IS_PROD) {
                $x = explode(".", $base->base_no_proto, 2);
                $base_full = $base->proto . "://" . $req['pattern'] . "." . end($x) . "/";
                $req['pattern'] = "*";
            }

            if(!$same_domain && $req['domain_type'] == DomainType::SUB) {
                $x = explode(".", $base->base_no_proto, 2);
                $base_full = $base->proto . "://" . end($x) . "/";
            }
        }

        $domain = $req['pattern'] == "*" ? "" : $req['pattern'];

        if($req['domain_type'] == DomainType::LOCAL)
            $domain = $domain ? $domain . "/" : $domain;
        else
            $domain = "";

        $this->link = $base_full . $domain . $link;

        return $this;
    }

    public function get_href() : string {
        return $this->link;
    }

    public function attr(string $attr) : self {
        $this->attr .= $attr;
        return $this;
    }

    public function class(string $class_name) : self {
        return $this->attr('class=" ' . $class_name . '"');
    }

    public function target(#[ExpectedValues(['_blank','_parent','_top','_self'])] string $target) : self {
        return $this->attr('target=" ' . $target . '"');
    }

    public function child(string $child) : string {
        return <<<LNK
            <a {$this->attr} href="{$this->link}">$child</a>
        LNK;
    }

}
