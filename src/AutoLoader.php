<?php
declare(strict_types=1);

namespace Lay;
abstract class AutoLoader
{
    private static string $slash = DIRECTORY_SEPARATOR;

    public static function get_root_dir(): string
    {
        return __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR;
    }

    public static function load_lay_classes(): void
    {
        spl_autoload_register(function ($className) {
            $location = self::get_root_dir() . str_replace('\\', self::$slash, $className) . ".php";

            if (file_exists($location))
                @include_once $location;
        });
    }

    public static function load_other_classes(?array $directories = null): void
    {
        spl_autoload_register(function ($className) use ($directories) {
            $location = str_replace('\\', self::$slash, $className);
            $clean_file = fn($location) => self::get_root_dir() . $location . ".php";

            if ($directories)
                foreach ($directories as $dir) {
                    $dir = trim($dir, self::$slash);
                    $file = $clean_file($dir . self::$slash . $location);

                    if (file_exists($file))
                        @include_once $file;
                }
            else {
                $location = str_replace('\\', self::$slash, $className);
                $file = $clean_file($location);

                if (file_exists($file))
                    @include_once $file;
            }
        });
    }

    public static function load_vendor_classes(): void
    {
        $autoloader = self::get_root_dir() . "vendor" . DIRECTORY_SEPARATOR . "autoload.php";

        if (file_exists($autoloader))
            @include_once $autoloader;
    }
}

AutoLoader::load_lay_classes();
# comment the line below if you're not interested in this package's autoloader
AutoLoader::load_other_classes();
# Load files from composer
AutoLoader::load_vendor_classes();
