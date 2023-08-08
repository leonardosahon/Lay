<?php
declare(strict_types=1);

use Lay\core\LayConfig;

if(!isset($BOB_D_BUILDER))
    \Lay\core\Exception::throw_exception("BAD REQUEST", "This script cannot be accessed this way, please return home");

function bob_d_builder(string $view) : void {
    $layConfig = LayConfig::instance();
    $link = fn($link = "") => $layConfig->get_site_data("base") . $link;

    $layConfig->view_const([
        "page" => [
            "type" => "front"
        ],
        "body" => [
            "class" => "home-body",
        ],
        "local" => [
            "link" => $link,
            "img" => $layConfig->get_res__client('front', 'img'),
            "img_custom" => $layConfig->get_res__client('custom', 'img'),
            "logo" => $layConfig->get_site_data('img', 'logo'),
            "section" => "app",
        ],
    ]);

    switch ($view){
        default: $layConfig->view([
            "page" => [
                "title" => "$view - Page not Found",
            ],
            "body" => [
                "class" => "defult-home",
            ],
            "local" => [
                "section" => "error",
            ],
            "view" => [
                "body" => "error",
            ],
        ]); break;
        case "index":
            $layConfig->view([
                "page" => [
                    "title" => "Homepage",
                    "desc" => "This is a sample description for a Lay Page"
                ],
                "body" => [
                    "class" => "defult-home"
                ],
                "view" => [
                    "body" => "homepage"
                ],
                "assets" => [
                    "@custom/js/index.js"
                ]
            ]);
        break;
        case "another-page":
            $layConfig->view([
                "page" => [
                    "title" => "Another Page",
                    "desc" => "This is a sample description for a another Lay Page, it's that easy"
                ],
                "view" => [
                    "body" => "another"
                ],
            ]);
        break;
        
    }
}
