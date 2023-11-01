<?php
declare(strict_types=1);
namespace Lay\core\view;

use Lay\core\LayConfig;

final class ViewSrc {
    public static function gen(string $src) : string {
        $client = LayConfig::res_client();
        $base = LayConfig::site_data()->base;
        
        $src = str_replace(
            [
                "@front/", "@back/", "@custom/",
                "@front_js/", "@back_js/", "@custom_js/",
                "@front_img/", "@back_img/", "@custom_img/",
                "@front_css/", "@back_css/", "@custom_css/",
            ],
            [
                $client->front->root, $client->back->root, $client->custom->root,
                $client->front->js, $client->back->js, $client->custom->js,
                $client->front->img, $client->back->img, $client->custom->img,
                $client->front->css, $client->back->css, $client->custom->css,
            ],
            $src
        );

        if(!str_starts_with($src, $base))
            return $src;

        $local_file = str_replace($base, "", $src);

        try {
            $src .= "?mt=" . @filemtime($local_file);
        } catch (\Exception) {}

        return $src;
    }
}
