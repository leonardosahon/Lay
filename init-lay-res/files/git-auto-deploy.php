<?php

if(!($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['dep1'] === "a-pull" && $_GET['dep2'] === "b-pull" && $_GET['dep3'] === "c-pull")) {
    header("location: ./");
    die;
}

print "Auto deploy Responds With: \n";

$post = json_decode($_POST['payload']);

if(isset($post->pull_request)) {

    if($post->pull_request->state == "closed") {
        echo shell_exec('git checkout main 2>&1');
        echo shell_exec('git pull 2>&1');
        echo shell_exec('git reset --hard origin/main 2>&1');
        echo shell_exec('export HOME=./ && composer install 2>&1');
    }

    else
        echo "Pull Request: " . $post->pull_request->state;

    die;
}

echo $post?->action?->zen;
