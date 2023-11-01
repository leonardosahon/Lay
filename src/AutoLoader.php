<?php
declare(strict_types=1);

namespace Lay;
class AutoLoader
{
    private static string $slash = DIRECTORY_SEPARATOR;
    private const AUTOLOADER_DIR = "Lay";
    private static self $instance;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    public static function instance(): self
    {
        if (!isset(self::$instance))
            self::$instance = new self();
        return self::$instance;
    }

    public static function get_root_dir() : string
    {
        return str_replace(self::AUTOLOADER_DIR, "", __DIR__);
    }

    public static function load_lay_classes() : void
    {
        spl_autoload_register(function ($className) {
            $location = str_replace('\\', self::$slash, $className);
            $root_namespace = explode(self::$slash, $location);
            $location = str_replace(self::AUTOLOADER_DIR, "", __DIR__) . $location . ".php";

            if (file_exists($location))
                @include_once $location;
        });
    }

    public static function load_other_classes(?array $directories = null) : void
    {
        spl_autoload_register(function ($className) use ($directories) {
            $location = str_replace('\\', self::$slash, $className);
            $clean_file = fn($location) => str_replace(self::AUTOLOADER_DIR, "", __DIR__ . self::$slash . $location . '.php');

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

        if (!file_exists($autoloader))
            return;

        @include_once $autoloader;
    }
}

AutoLoader::load_lay_classes();
# comment the line below if you're not interested in this package's autoloader
AutoLoader::load_other_classes();
# Load files from composer
AutoLoader::load_vendor_classes();
