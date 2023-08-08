<?php
declare(strict_types=1);
namespace Lay\libs;

use Closure;

class CopyDirectory {
    /**
     * @param string $src_dir
     * @param string $dest_dir
     * @param Closure|null $ignore_flags
     * It should return true or false when the file to be copied has been tested
     * against whatever conditions you have implemented in the closure.
     * Example: fn($file) => $file == "node_modules"
     * @param Closure|null $pre_copy a callback function that should happen before copy happens
     * @param Closure|null $post_copy a callback function that should happen after copy has occurred
     */
    public function __construct(string $src_dir, string $dest_dir, ?Closure $ignore_flags = null, ?Closure $pre_copy = null, ?Closure $post_copy = null) {
        $dir = opendir($src_dir);

        if($ignore_flags === null)
            $ignore_flags = fn() => false;

        if($pre_copy === null)
            $pre_copy = fn() => false;
        if($post_copy === null)
            $post_copy = fn() => false;

        if (!is_dir($dest_dir)) {
            umask(0);
            mkdir($dest_dir,0777,true);
        }

        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..' || $ignore_flags($file))
                continue;

            if (is_dir("$src_dir/$file")) {
                $this->__construct("$src_dir/$file", "$dest_dir/$file", $ignore_flags, $pre_copy, $post_copy);
            }
            else {
                $pre = $pre_copy($file,$src_dir,$dest_dir);

                if($pre == "continue")
                    continue;

                if($pre == "break")
                    break;

                if($pre == "skip-copy" && ($post = $post_copy($file,$src_dir,$dest_dir) != "copy"))
                    continue;

                copy("$src_dir/$file", "$dest_dir/$file");

                if(!isset($post)) {
                    $post_copy($file, $src_dir, $dest_dir);
                }
            }
        }

        closedir($dir);
    }
}