<?php
require_once "CopyDirectory.php";

class Core {
    private static string $init_lay_files = "init-lay-res" . DIRECTORY_SEPARATOR . "files";
    private static array $ag;
    private static string $default_lay_version;
    private static string $default_lay_location;
    private static string $current_project_location;
    private static bool $update_lay_version_by_default;

    public function __construct(
        array $arguments,
        string $version,
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
        print "This is the quickest and easiest way to initiate a project using Lay a lite php framework\n";
        print "----------------------------------------------------------\n";
        print "Usage: php $script_name [PROJECT_LOCATION] [-v <LAY_VERSION>] [-l <LAY_PACKAGE_LOCATION>] 
        \t[-w <OVERWRITE_EXISTING_PROJECT>] [-u <UPDATE_OR_DOWNGRADE_LAY>]\n";
        print "Example: php $script_name clients/a-new-project -v 1.0.0 -l library/Lay -w false -u true\n";
        print"### Keywords ###\n\n".
        "Keyword \t\t\t\t|\t Value \t\t|\t Required\n\n".
        "-v (VERSION_OF_LAY_TO_USE)         \t|\t true||false  \t|\t    false\n".
        "-w (OVERWRITE_EXISTING_PROJECT)    \t|\t true||false  \t|\t    false\n".
        "-u (UPDATE_OR_DOWNGRADE_LAY)       \t|\t true||false  \t|\t    false\n".
        "-l (LAY_PACKAGE_LOCATION)          \t|\t PATH_TO_LAY  \t|\t    false\n\n";
        print "This script doesn't overwrite a project or update a project's Lay package by default\n";
        die;
    }
    
    public function use_error($msg) {
        print "####\n";
        print "# Error Encountered \n";
        print "- What happened? $msg\n";
        print "####\n";
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

    public function copy_routine(string $target_dir, ?string $source = null, ?string $target_name = null) {
        if (self::$update_lay_version_by_default)
            return null;

        $this->mkdir_or_cp_file($target_dir);

        if($source)
            $this->mkdir_or_cp_file($target_dir,self::$init_lay_files . DIRECTORY_SEPARATOR . $source, $target_name ?? $source);
    }

    public function create_project($lay,$project_root) : void {
        $sections = ["__front","__back"];
        $sections_js = ["front","back"];
        $s = DIRECTORY_SEPARATOR;

        $this->copy_routine("api","api-index.php","index.php");

        $inc = "res{$s}server{$s}includes$s";
        $view = "res{$s}server{$s}view$s";
        $client = "res{$s}client{$s}dev$s";

        $this->copy_routine($inc,"connection.inc");

        // server files
        for($i = 0; $i < count($sections); $i++){
            $section = $sections[$i];

            $this->copy_routine("res{$s}server{$s}model{$s}{$section}");
            $this->copy_routine("res{$s}server{$s}view{$s}{$section}");
            $this->copy_routine("res{$s}server{$s}controller{$s}{$section}");

            $this->copy_routine($inc . "$section","body.inc");
            $this->copy_routine($inc . "$section","head.inc");
            $this->copy_routine($inc . "$section","script.inc");
            
            $this->copy_routine($view . "$section","homepage.view");
            $this->copy_routine($view . "$section","error.view");
        }

        // client files
        for($i = 0; $i < count($sections_js); $i++){
            $this->copy_routine($client . $sections_js[$i]);
        }

        $section = $client . "custom{$s}";
        $this->copy_routine($section . "css");
        $this->copy_routine($section . "js");
        $this->copy_routine($section . "images");
        $this->copy_routine($section . "plugin");

        $this->copy_routine($section . "images","icon.png");
        $this->copy_routine($section . "images","favicon.png");
        $this->copy_routine($section . "images","logo.png");

        // copy specified Lay version
        new CopyDirectory($lay, $project_root . $s . "Lay");

        // copy default root folder files
        $this->copy_routine("", "favicon.png","favicon.ico");
        $this->copy_routine("", "bob_d_builder.php");
        $this->copy_routine("", "index.php");
        $this->copy_routine("", "layconfig.php");
        $this->copy_routine("", "htaccess", ".htaccess");
        $this->copy_routine("", "gitignore", ".gitignore");
        $this->copy_routine("", "robots.txt");
    }
}