<?php
declare(strict_types=1);

namespace res\server\view;

use Lay\core\LayConfig;
use Lay\core\LayView;
use Lay\core\ViewTemplate;

class DefaultViews extends ViewTemplate
{
    public function init_pages(): void
    {
        $layConfig = LayConfig::new();

        $this->view->init_start()
            ->page('type', 'front')
            ->body_tag("default-home", 'id="new-body"')
            ->local("link", fn($link = "") => $layConfig->get_site_data("base") . $link)
            ->local("others", $layConfig->get_site_data('others'))
            ->local("img", $layConfig->get_res__client('front', 'img'))
            ->local("img_custom", $layConfig->get_res__client('custom', 'img'))
            ->local("logo", $layConfig->get_site_data('img', 'logo'))
        ->init_end();
    }


    public function pages(): void
    {
        $this->view->route("index")->bind(function (LayView $layView) {
            $layView->connect_db()
                ->page("title", "Homepage")
                ->page("desc", "This is the default homepage description")
                ->body("homepage");
        });

        $this->view->route("another-page")->bind(function (LayView $layView) {
            $layView->connect_db()
                ->page("title", "Another Page")
                ->page("desc", "This is another page's description")
                ->body("another");
        });
    }

    public function default(): void
    {
        $this->view->route($this->view::DEFAULT_ROUTE)->bind(function (LayView $layView){
            $layView->page('title', $this->view->request('route') . " - Page not Found")
                ->body_tag("defult-home")
                ->local("current_page", "error")
                ->local("section", "error")
                ->body('error');
        });
    }
}
