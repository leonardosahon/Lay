<?php
class Core {
    private static string $init_lay_files = "init-lay-res" . DIRECTORY_SEPARATOR . "files";
    private static array $ag;
    private static float $default_lay_version;
    private static string $default_lay_location;
    private static string $current_project_location;
    private static bool $update_lay_version_by_default;

    public function __construct(
        array $arguments,
        float $version,
        string $lay_location
    ){
        self::$ag = $arguments;
        self::$default_lay_version = $version;
        self::$default_lay_location = $lay_location . DIRECTORY_SEPARATOR;
    }
    
    public function set_current_project(string $project) : void {
        self::$current_project_location = $project;
    }

    public function set_update_lay(bool $switch) : void {
        self::$update_lay_version_by_default = $switch;
    }

    public function intro($kill = false) {
        $ver = self::$default_lay_version;
        print "----------------------------------------------------------\n";
        print "-- Name:     \t  Lay Project Initiator                    \n";
        print "-- Version:  \t  $ver                                     \n";
        print "-- Author:   \t  Osahenrumwen Aigbogun                    \n";
        print "-- Created:  \t  08/02/2022;                              \n";
        print "----------------------------------------------------------\n";
        if ($kill) die;
    }
    
    public function help() {
        $script_name = "init-lay";
        
        $this->intro();
        print ">>> This is a quick way to initiate a project using Lay as the framework. This script should reside at the root folder \n";
        print "----------------------------------------------------------\n";
        print "Arguments Available\n";
        print "Arguments Available\n";
        print "----------------------------------------------------------\n";
        print "### Usage: [$script_name] {PROJECT_LOCATION} -v {LAY_VERSION (optional)} -l {LAY_PACKAGE_LOCATION (optional)} -w {OVERWRITE_EXISTING_PROJECT (optional)} -u {UPDATE_LAY_VERSION (optional)}\n";
        print "### Example: php $script_name clients/a-new-project -v 1.0.0 -l library/Lay -w false -u true\n";
        die;
    }
    
    public function use_error($msg) {
        print "####\n";
        print "# Error Encountered \n";
        print "# What happened? $msg\n";
        die;
    }

    public function set_flag($index, &$version, &$location, &$overwrite, &$update) {
        $ag = self::$ag;
        $flag = @$ag[$index + 1];
        switch (substr($ag[$index], 0, 2)) {
            default:
                break;
            case '-v':
                $version = $flag;
                break;
            case '-l':
                $location = $flag;
                break;
            case '-w':
                $overwrite = (bool)$flag;
                break;
            case '-u':
                $update = (bool)$flag;
                break;
        }
    }

    private function mkdir_or_cp_file(string $current_project_dir, ?string $s_name = null, ?string $t_name = null) {
        $s = DIRECTORY_SEPARATOR;

        $dir = self::$current_project_location . $s . $current_project_dir;
        if ($s_name && $t_name)
            return copy(self::$default_lay_location . $s_name, $dir . $s . $t_name);

        umask(0);
        if (!is_dir($dir) && @!mkdir($dir, 0775, true))
            $this->use_error("Failed to create directory on location: ($dir); access denied; modify permissions and try again");
    }

    public function copy_routine(string $locale, ?string $source = null, ?string $target = null) {
        if (self::$update_lay_version_by_default == true) 
            return null;

        $this->mkdir_or_cp_file($locale);

        if($source)
            $this->mkdir_or_cp_file($locale,self::$init_lay_files . DIRECTORY_SEPARATOR . $source, $target ?? $source);
    }

    public function folder_copy(string $sourceDirectory, string $destinationDirectory, string $childFolder = '') {
        $dir = opendir($sourceDirectory);
    
        if (is_dir($destinationDirectory) === false) {
            mkdir($destinationDirectory);
        }
    
        if ($childFolder !== '') {
            if (is_dir("$destinationDirectory/$childFolder") === false) {
                mkdir("$destinationDirectory/$childFolder");
            }
    
            while (($file = readdir($dir)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
    
                if (is_dir("$sourceDirectory/$file") === true) {
                    $this->folder_copy("$sourceDirectory/$file", "$destinationDirectory/$childFolder/$file");
                } else {
                    copy("$sourceDirectory/$file", "$destinationDirectory/$childFolder/$file");
                }
            }
    
            closedir($dir);
    
            return;
        }
    
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..')
                continue;
    
            if (is_dir("$sourceDirectory/$file") === true)
                $this->folder_copy("$sourceDirectory/$file", "$destinationDirectory/$file");
            else
                copy("$sourceDirectory/$file", "$destinationDirectory/$file");
        }
    
        closedir($dir);
    }

}