<?php

namespace Lay\libs;

use Lay\core\traits\IsSingleton;

class LayWordCount
{
    private int $words_per_minute = 265;

    public function wpm(int $words_per_minute) : self {
        $this->$words_per_minute = $words_per_minute;
        return $this;
    }

    use IsSingleton;

    public function text(string $words) : array {
        $words = explode(" ", trim(strip_tags($words)));
        $count = count($words);

        return [
            "total" => $count,
            "duration" => ceil($count/$this->words_per_minute)
        ];
    }
}