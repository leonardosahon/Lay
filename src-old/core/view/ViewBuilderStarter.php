<?php

namespace Lay\core\view;

abstract class ViewBuilderStarter
{
    public readonly ViewBuilder $builder;

    public function __construct() {
        if(!isset($this->builder))
            $this->builder = ViewBuilder::new();
    }

    public function init_pages(): void
    {
        $this->builder->init_start()
            ->page('type', 'front')
            ->page('section', 'app');
        $this->builder->init_end();
    }

    final public function init(): void
    {
        if(!isset($this->builder))
            $this->builder = ViewBuilder::new();

        $this->init_pages();
        $this->default();
        $this->pages();

        $this->builder->end();
    }

    public function pages(): void
    {
        $this->builder->route("index")->bind(function (ViewBuilder $builder, array $init_values) {
            $builder->page("title", "Default Lay Page")
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
        $this->builder->route($this->builder::DEFAULT_ROUTE)->bind(function (ViewBuilder $builder, array $init_values) {
            $builder
                ->page("title", $builder->request('route') . " - Page not found")
                ->body_tag("defult-home")
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
