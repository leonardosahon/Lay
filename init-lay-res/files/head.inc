<?php
global $view;
\Lay\core\view\tags\Link::new()->rel("stylesheet")->href("@custom/css/style.css");
?>

<style>
    body{
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        color: #041039;
        flex-flow: column;
        font-size: 1.2rem;
    }
</style>

<?= $view->head ?>
