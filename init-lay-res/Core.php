<?php
require_once "CopyDirectory.php";

class Core {
    private static string $init_lay_files = "init-lay-res" . DIRECTORY_SEPARATOR . "files";
    private static array $ag;
    private static string $default_lay_location;
    private static string $current_project_location;
    private static bool $always_update_lay_package = true;
    private static bool $always_overwrite_project = false;
    private static bool $project_exists = false;
    private static object $composer;
    public string $project_name;

    public function __construct(
        array $arguments,
        string $lay_location
    ){
        self::$ag = $arguments;
        self::$composer = json_decode(file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "src" . DIRECTORY_SEPARATOR . "composer.json"));
        self::$default_lay_location = $lay_location . DIRECTORY_SEPARATOR;
    }
    
    public function set_current_project(string $project) : void {
        self::$current_project_location = $project;
    }

    public function set_project_name(string $project) : void {
        $x = explode("/", trim($project, "/"));
        $project = end($x);
        $this->project_name = $project;
    }

    public function project_name() : string {
        return self::$project_name ?? "";
    }

    public function set_update_lay(bool $switch) : void {
        self::$always_update_lay_package = $switch;
    }

    public function set_overwrite(bool $switch) : void {
        self::$always_overwrite_project = $switch;
    }

    public static function project_exists(): void
    {
        self::$project_exists = true;
    }

    public function intro($kill = false) {
        $ver = self::$composer->version;
        $created = self::$composer->time;
        $updated= self::$composer->modified;
        $project= self::$composer->name;
        $home= self::$composer->homepage;

        print "----------------------------------------------------------\n";
        print "-- Name:     \t  $project                                 \n";
        print "-- Version:  \t  $ver                                     \n";
        print "-- Author:   \t  Osahenrumwen Aigbogun                    \n";
        print "-- Created:  \t  $created                                 \n";
        print "-- Updated:  \t  $updated                                 \n";
        print "-- Homepage: \t  $home                                    \n";
        print "----------------------------------------------------------\n";
        if ($kill) die;
    }
    
    public function help() {
        $script_name = "init-lay";
        $overwrite = str_replace(1, "true", self::$always_overwrite_project);
        $overwrite = $overwrite == "" ? 'false' : $overwrite;
        $update = str_replace(1, "true", self::$always_update_lay_package);
        $update= $update== "" ? 'false' : $update;
        
        $this->intro();
        print "This is the quickest and easiest way to initiate a project using Lay a lite php framework\n";
        print "----------------------------------------------------------\n";
        print "Usage: php $script_name [PROJECT_LOCATION] [-l <LAY_PACKAGE_LOCATION>] [-w <OVERWRITE_EXISTING_PROJECT>] [-u <UPDATE_OR_DOWNGRADE_LAY>]\n";
        print "Example: php $script_name clients/a-new-project -l my-libraries/Lay -w false -u true\n";
        print"\n### Keywords ###\n".
        "Keyword \t\t\t\t| Value \t|\t Required  |\t Default\n";
        print "--------------------------------------------------------------------------------------------\n";
        print "-w (OVERWRITE_EXISTING_PROJECT)  \t| true||false   |\t    false  |\t " . $overwrite . "\n".
        "-u (UPDATE_OR_REFRESH_LAY)       \t| true||false   |\t    false  |\t " . $update . "\n".
        "-l (LAY_PACKAGE_LOCATION)        \t| PATH_TO_LAY   |\t    false  |\t " . self::$default_lay_location . "\n\n";
        die;
    }
    
    public function use_error($msg) {
        print "\n#### Error Encountered ###\n";
        print "# What Happened? # \n$msg";
        print "\n####\n";
        die;
    }

    public function set_flags($index, &$location, &$overwrite, &$update) {
        $ag = self::$ag;
        $flag = @$ag[$index + 1];
        switch (substr($ag[$index], 0, 2)) {
            default:
                break;
            case '-l':
                $location = $flag;
                break;
            case '-w':
                $overwrite = filter_var($flag, FILTER_VALIDATE_BOOL);
                break;
            case '-u':
                $update = filter_var($flag, FILTER_VALIDATE_BOOL);
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
        if (file_exists($target_dir . $target_name) && !self::$always_overwrite_project)
            return null;

        $this->mkdir_or_cp_file($target_dir);

        if($source)
            $this->mkdir_or_cp_file($target_dir,self::$init_lay_files . DIRECTORY_SEPARATOR . $source, $target_name ?? $source);
    }

    public function create_project($lay, $project_root) : void {
        $sections = ["__front","__back"];
        $sections_js = ["front","back"];
        $s = DIRECTORY_SEPARATOR;
        $lay = $lay . "src" . $s;

        // copy specified Lay version
        if(!self::$project_exists || (self::$project_exists && self::$always_update_lay_package))
            new CopyDirectory($lay, $project_root . $s . "Lay");

        if(self::$project_exists && !self::$always_overwrite_project)
            return;

        $inc = "res{$s}server{$s}includes$s";
        $view = "res{$s}server{$s}view$s";
        $client = "res{$s}client{$s}dev$s";

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
            $this->copy_routine($view . "$section","another.view");
            $this->copy_routine($view . "$section","error.view");
        }

        // client files
        for($i = 0; $i < count($sections_js); $i++){
            $this->copy_routine($client . $sections_js[$i]);
        }

        $section = $client . "custom{$s}";
        $this->copy_routine($section . "css");
        $this->copy_routine($section . "css","style.css");
        $this->copy_routine($section . "js","index.js");
        $this->copy_routine($section . "js","another.js");
        $this->copy_routine($section . "images");
        $this->copy_routine($section . "plugin");

        $this->copy_routine($section . "images","icon.png");
        $this->copy_routine($section . "images","favicon.png");
        $this->copy_routine($section . "images","logo.png");

        // copy default files
        $this->copy_routine("","api.php");

        $this->copy_routine($inc . "__env" . $s . "__db" . $s, "connection.lenv", "dev.lenv");
        $this->copy_routine($inc . "__env" . $s . "__db" . $s, "connection.lenv", "prod.lenv");
        $this->copy_routine($inc . "__env" . $s, "smtp.lenv", "smtp.lenv");

        $this->copy_routine("res{$s}server{$s}controller{$s}__front{$s}", "EndUsers.php");
        $this->copy_routine("res{$s}server{$s}utils{$s}", "Email.php");
        $this->copy_routine("res{$s}server{$s}view{$s}", "DefaultViews.php");

        $this->copy_routine("", "favicon.ico");
        $this->copy_routine("", "index.php");
        $this->copy_routine("", "layconfig.php");
        $this->copy_routine("", "git-auto-deploy.php");
        $this->copy_routine("", "htaccess", ".htaccess");
        $this->copy_routine("", "gitignore", ".gitignore");
        $this->copy_routine("", "robots.txt");

        // Create a composer.json file on the project root
        $fh = fopen(self::$current_project_location . $s . "composer.json", "w");
        fwrite($fh, json_encode (
            [
                "require" => self::$composer->require
            ],
            JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES
        ));
        fclose($fh);

        // Create a package.json file on the project root
        $fh = fopen(self::$current_project_location . $s . "package.json", "w");
        fwrite($fh, json_encode (
            [
                "name" => $this->project_name,
                "version" => "1.0.0",
                "private" => true,
                "author" => "lay <hello@lay.osaitech.dev> (https://lay.osaitech.dev)",
                "copyright" => "Lay - a lite PHP Framework ( https://lay.osaitech.dev/ ). All rights reserved.",
                "dependencies" => self::$composer->extra->{"npm-packages"},
            ], JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES
        ));
        fclose($fh);
    }
}
