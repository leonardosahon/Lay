<?php

namespace Lay\libs;

use Lay\core\traits\IsSingleton;

class LayWordCount
{
    private int $words_per_minute = 265;
    private int $secs_allocated_to_img = 6;
    private int $secs_allocated_to_video = 3;
    private int $secs_allocated_to_audio = 2;
    private int $extra_secs = 0;

    public function wpm(int $words_per_minute) : self {
        $this->$words_per_minute = $words_per_minute;
        return $this;
    }

    public function extra_secs(int $extra_secs) : self {
        $this->extra_secs = $extra_secs;
        return $this;
    }

    public function img_allocation(int $secs_allocated_to_img) : self {
        $this->secs_allocated_to_img = $secs_allocated_to_img;
        return $this;
    }

    public function audio_allocation(int $secs_allocated_to_audio) : self {
        $this->secs_allocated_to_audio = $secs_allocated_to_audio;
        return $this;
    }

    public function video_allocation(int $secs_allocated_to_video) : self {
        $this->secs_allocated_to_video = $secs_allocated_to_video;
        return $this;
    }

    use IsSingleton;

    public function text(string $words) : array {
        $dom = (new \DOMDocument('1.0'));

        @$dom->loadHTML($words);

        $words = explode(" ", trim(strip_tags($words)));
        $words = count($words);

        $img_count = $dom->getElementsByTagName("img")->count() * $this->secs_allocated_to_img;
        $video_count = $dom->getElementsByTagName("video")->count() * $this->secs_allocated_to_video;
        $audio_count = $dom->getElementsByTagName("audio")->count() * $this->secs_allocated_to_audio;

        $duration = $words + $img_count + $video_count + $audio_count + $this->extra_secs;

        return [
            "total" => $words,
            "duration" => ceil($duration/$this->words_per_minute)
        ];
    }
}
