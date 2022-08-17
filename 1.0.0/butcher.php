#!/usr/bin/php
<?php
$intro = function() {
    print "----------------------------------------------------------\n";
    print "-- Name:     \t  OsaiMinifier                             \n";
    print "-- Version:  \t  1.1                                      \n";
    print "-- Author:   \t  Osahenrumwen Aigbogun                    \n";
    print "-- Created:  \t  21/10/2021;                              \n";
    print "-- Dependencies:\n \tTerser (JS);\n \tclean-css-cli (CSS) \n";
    print "----------------------------------------------------------\n";
};

$args = $argv;
$script_name = "butcher.php";

if(in_array(("--help"),$args,true) || in_array(("-h"),$args,true)){
    $intro();
    print ">>> This is a batch minifier for JS/CSS, that takes a directory as an input and provides the respected output in the\n";
    print ">>> directory indicated using the -o flag. This helps save production time\n";
    print "----------------------------------------------------------\n";
    print "### Usage: [$script_name] {directory_name} [--output || -o] {output_directory} [--ext || -e] {js || css}\n";
    print "### Example: php $script_name dir/js -o prod-dir/js -e js\n";
    die;
}

if($argc == 1){
    $intro();
    die;
}

$raw_dir = $args[1];

$out = array_search("--output",$args);
if($arg = !$out ? array_search("-o",$args) : $out)
    $bundle_dir = $args[($arg + 1)];

$xten = array_search("--ext",$args);
if($arg = !$xten ? array_search("-e",$args) : $xten)
    $ext = $args[($arg + 1)];

 $ignore = array_search("--ignore",$args);
 if($arg = !$ignore ? array_search("-i",$args) : $ignore)
     $ign = explode(",",$args[($arg + 1)]);

if(!isset($bundle_dir)) {
    print "No output directory was specified, process aborted! use --help for more info\n";
    die;
}

if(!isset($ext)) {
    print "No file extension was specified, process aborted! use --help for more info\n";
    die;
}

minify_dir($raw_dir,$bundle_dir,$ext);

function minify_dir(string $parent_dir, string $output_dir, string $ext, bool $start = true, array &$error = []) {
    global $ign;
    $ignore = $ign ?? [];
    $core_ignore = ["node_modules"];
    
    if($start) {
        $GLOBALS['intro']();
        print "### Production Begins\n";
        print "=== Type: " . strtoupper($ext) . " ===\n";
    }

    $ext = strtolower($ext);
    $last_file = 'No File Processed';

    if(is_dir($parent_dir)) {
        $parent_dir_files = scandir($parent_dir);

        if(!is_dir($output_dir)) {
            umask(0);
            mkdir($output_dir,0777,true);
        }

        foreach ($parent_dir_files as $file) {
            if($file == "." || $file == ".." ||
                in_array($file,$core_ignore,true) ||
                in_array($file,$ignore,true) ||
                (function_exists('fnmatch') && fnmatch('.*',$file)))
                continue;

            $output = $output_dir . "/" . $file;
            $file = $parent_dir . "/" . $file;
            $ext_extract = explode(".$ext", $file);

            if(is_dir($file)){
                minify_dir($file, $output, $ext, false, $error);
                continue;
            }

            # Ensure the file has the selected file extension and nothing else
            if (count($ext_extract) == 2 && empty(explode(".$ext", $ext_extract[1])[0])) {
                $current_error = [];
                $last_file = $file;

                print "\033[2K\r=== Current File: $file";

                if($ext === "js")
                    exec("terser $file -c -m -o $output 2>&1",$current_error);
                elseif ($ext === "css")
                    exec("cleancss -o $output $file 2>&1", $current_error);

                if(count($current_error) > 0)
                    array_push($error,["file" => $file, "error" => join("\n",$current_error)]);
            }
        }
    }
    else {
        print "Argument[0] is not a directory; Argument[0] & Argument[1] should be directories\n";
        die;
    }
    if($start) {
        $error_count = count($error);
        print "\033[2K\r=== Last File: $last_file";
        print "\n### Production ENDS ===\n";
        print "### Errors: $error_count\n";
        if($error_count > 0) print_r($error);
    }
}
