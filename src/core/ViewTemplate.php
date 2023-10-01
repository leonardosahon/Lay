<?php

namespace Lay\core;

abstract class ViewTemplate
{
    public readonly LayView $view;

    public function init_pages(): void
    {
        $this->view->init_start()
            ->page('type', 'front')
            ->page('section', 'app');
        $this->view->init_end();
    }

    final public function init(): void
    {
        if(!isset($this->view))
            $this->view = LayView::new();

        $this->init_pages();
        $this->default();
        $this->pages();

        $this->view->end();
    }

    public function pages(): void
    {
        $this->view->route("index")->bind(function (LayView $layView, array $init_values) {
            $layView->page("title", "Default Lay Page")
                ->page("desc", "A default description. This goes to the meta tags responsible for the page description")
                ->local("current_page", "home")
                ->body("homepage");
        });
    }

    /**
     * This page loads when no route is found.
     * It can be used as a 404 error page
     * @return void
     */
    public function default(): void
    {
        $this->view->route($this->view::DEFAULT_ROUTE)->bind(function (LayView $layView, array $init_values) {
            $layView
                ->page("title", $layView->request('route') . " - Page not found")
                ->body_attr("defult-home")
                ->local("current_page", "error")
                ->local("section", "error")
                ->body(function (array $meta) { ?>
                    <style>
                        .return{
                            color: #fff;
                            font-weight: 600;
                            text-decoration: none;
                            background: transparent;
                            border: solid 1px;
                            padding: 10px;
                            border-radius: 30px;
                            transition: all ease-in-out .3s;
                        }
                        .return:hover{
                            background: #fff;
                            border-color: #fff;
                            color: #000;
                        }
                    </style>
                    <h1><?= $meta['page']['title'] ?></h1>
                    <p>This is the default error page of Lay Framework</p>
                    <a class="return" href="<?= \Lay\core\LayConfig::new()->get_site_data('base') ?>">Return Home</a>
                <?php });
        });
    }
}