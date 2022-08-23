<?php
declare(strict_types=1);
namespace Lay;
class AutoLoader {
    private static string $slash = DIRECTORY_SEPARATOR;
    private static string $autoloader_dir = "Lay";
    public static function get_root_dir(){
        return str_replace(self::$autoloader_dir, "",__DIR__);
    }
    public static function load_lay_classes(){
        spl_autoload_register(function ($className){
            $location = str_replace('\\',self::$slash, $className);
            $root_namespace = explode(self::$slash,$location);
            $location = str_replace(self::$autoloader_dir, "",__DIR__) .  $location . ".php";
            if (file_exists($location))
                @include_once $location;
        });
    }
    public static function load_other_classes(?array $directories = null){
        spl_autoload_register(function ($className) use ($directories){
            $location = str_replace('\\',self::$slash, $className);
            $clean_file = fn($location) => str_replace(self::$autoloader_dir,"",__DIR__ . self::$slash . $location . '.php');

            if ($directories)
                foreach ($directories as $dir){
                    $dir = rtrim($dir,self::$slash);
                    $dir = ltrim($dir,self::$slash);
                    $file = $clean_file($dir . self::$slash . $location);
                    if (file_exists($file))
                        @include_once $file;
                }
            else{
                $location = str_replace('\\',self::$slash, $className);
                $file = $clean_file($location);
                if (file_exists($file))
                    @include_once $file;
            }
        });
    }
}
AutoLoader::load_lay_classes();
# comment the line below if you're not interested in this package's autoloader
AutoLoader::load_other_classes();