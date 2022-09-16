<?php

class CopyDirectory {

    public function __construct(string $src_dir, string $dest_dir) {
        $dir = opendir($src_dir);

        if (!is_dir($dest_dir)) {
            umask(0);
            mkdir($dest_dir,0777,true);
        }

        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..')
                continue;

            if (is_dir("$src_dir/$file"))
                $this->__construct("$src_dir/$file", "$dest_dir/$file");

            else
                copy("$src_dir/$file", "$dest_dir/$file");
        }

        closedir($dir);
    }
}