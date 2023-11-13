<?php
declare(strict_types=1);

namespace Lay\core\view\tags\traits; 


use Lay\core\enums\CustomContinueBreak;

trait Standard {
    private static self $me;
    
    private array $attr = [];

    public static function new() : self {
        self::$me = new self();
        
        return self::$me;
    }

    public static function clear() : void {
        self::$me->attr = self::ATTRIBUTES ?? [];
    }
    
    public function attr(string $key, string $value) : self {
        $this->attr[$key] = $value;
        return $this;
    }
    
    private function get_attr(?\Closure $callback = null) : string {
        $attr = "";
        
        foreach($this->attr as $key => $value) {
            if($callback) {
                $rtn = $callback($value, $key);

                if($rtn == CustomContinueBreak::CONTINUE)
                    continue;
            }

            $attr .= $key . '="' . $value . '" ';
        }
        
        return $attr;
    }
}
