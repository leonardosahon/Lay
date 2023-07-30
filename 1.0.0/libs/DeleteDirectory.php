<?php
declare(strict_types=1);
namespace Lay\libs;

class DeleteDirectory {
    public static bool $result;

    /**
     * @param string $dir Directory to be deleted
     * @param string|null $break_directory optional child directory to stop delete recursion, before the root directory on arg 1 [$dir]
     */
    public function __construct(string $dir, ?string $break_directory = null){
        if (!is_dir($dir)) {
            self::$result = false;
            return;
        }

        foreach (scandir($dir) as $object) {
            if ($object == "." || $object == "..")
                continue;

            if (filetype($dir . "/" . $object) == "dir") {
                if(rtrim($object,"/") == $break_directory)
                    break;

                new self($dir . "/" . $object);
                continue;
            }

            unlink($dir."/".$object);
        }

        self::$result = rmdir($dir);
    }
}