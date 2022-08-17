<?php
include_once "config_example.php";
\Lay\core\LayConfig::instance()->view([
    "core" => [
        "skeleton" => false,
        "strict" => false,
    ],
    "page" => [
        "title" => "Lay Framework",
        "type" => "front",
    ],
    "view" => [
        "head" => function($page) { ?>
            <style>
                body {
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: auto;
                    padding: 0;
                    background: #1e5f85;
                    color: #fff;
                    flex-flow: column;
                    font-size: 1.2rem;
                }
            </style>
        <?php },
        "body" => function($page) { ?>
            <h1><?php echo $page['page']['title'] ?></h1>
            <p>This is a sample page of Lay framework in action</p>
            <p>This example uses `Closure` as a way to represent view</p>
        <?php },
        "script" => function($page){?>
            <script>osNote("This is Lay Framework by OsAi Technologies")</script>
        <?php }
    ],
]);