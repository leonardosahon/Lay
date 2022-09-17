<?php
declare(strict_types=1);
namespace Lay\orm\EXTENSIONS;
use mysqli_result;

trait ResultGen {
    ///1_DIMENSION_ARRAY
    public function get_assoc(mysqli_result $run) : ?array { return mysqli_fetch_assoc($run); }
    public function get_row(mysqli_result $run) : ?array { return mysqli_fetch_row($run); }
    ///2_DIMENSION_ARRAY
    public function loop_assoc(mysqli_result $run) : ?array {
        $k = 0; $result = null;
        while ($x = mysqli_fetch_assoc($run)) {
            $result[$k] = $x;
            $k++;
        }
        return $result;
    }
    public function loop_row(mysqli_result $run) : ?array {
        $k = 0; $result = null;
        while ($x = mysqli_fetch_row($run)) {
            $result[$k] = $x;
            $k++;
        }
        return $result;
    }
}