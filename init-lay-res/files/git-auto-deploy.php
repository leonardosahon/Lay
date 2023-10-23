<?php
require_once "Lay/AutoLoader.php";

// Verify webhook from GitHub
// Ensure to change the $_GET queries and values, these are the default,
// and they may change with various versions of Lay;
// So it is RECOMMENDED you change every single one of them.
if(!($_SERVER['REQUEST_METHOD'] === 'POST' && $_GET['dep1'] === "a-pull" && $_GET['dep2'] === "b-pull" && $_GET['dep3'] === "c-pull")) {
    header("location: ./");
    die;
}

print "Auto deploy Responds With: \n";

$post = json_decode($_POST['payload']);

if(!isset($post->pull_request)) {
    echo $post?->action?->zen;
    die;
}

if($post->pull_request->state != "closed") {
    echo "Pull Request: " . $post->pull_request->state;
    die;
}

echo shell_exec('git checkout main 2>&1');
echo shell_exec('git pull 2>&1');
echo shell_exec('git reset --hard origin/main 2>&1');

// push composer deployment for later execution to avoid 504 (timeout error)
echo \Lay\libs\LayCron::new()
    ->job_id("update-composer-pkgs")
    ->every_minute()
    ->new_job("Lay/deploy_composer")['msg'];
