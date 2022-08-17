<?php
declare(strict_types=1);
namespace Lay\orm\EXTENSIONS;

trait MultiQuery {
    /**
     * @param string $query
     * @param int $debug
     * @return bool
     */
    public function query_multi(string $query, int $debug = 0) : bool {
        self::core()->query="<div>$query</div>";
        $option['debug'] = [$query,"MULTI"];
        $run = false;
        $link = self::core()->get_link();

        if($debug) self::core()->show_exception(-1,$option['debug']);
        if (@mysqli_multi_query($link,$query)) {
            do {
                if ($run = mysqli_store_result($link)) {
                    ResultGen::instance()->loop_row($run);
                    mysqli_free_result($run);
                }
            } while (mysqli_next_result($link));
            $run = true;
        }
        else self::core()->show_exception(0,$option['debug']);
        return $run;
    }
}