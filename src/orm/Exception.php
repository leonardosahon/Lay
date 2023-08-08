<?php
declare(strict_types=1);
namespace Lay\orm;
use Lay\AutoLoader;

/**
 * Trait Exception
 * @package osai\SQL_MODEL
 */
class Exception {
    private static string $ENV = "DEVELOPMENT";
    public function set_env(string $ENV){
        $ENV = strtolower($ENV);
        self::$ENV = strtoupper(($ENV == "dev" || $ENV == "development") ? "development" : "production");
    }

    public function get_env(): string { return self::$ENV; }

    public function use_exception(string $title, string $body, bool $kill = true, bool $trace = true, array $raw = []) : void {
        $this->show_exception(-8,["title" => $title, "body_includes" => $body, "kill" => $kill, "trace" => $trace, "raw" => $raw]);
    }

    private function container ($title,$body,$other=[]) : string {
        if($other['core'] == "error") {$title_color = "#ff0014"; $body_color = "#ff5000";}
        elseif($other['core'] == "success") {$title_color = "#1cff03"; $body_color = "#1b8b07";}
        else{$title_color = "#5656f5"; $body_color = "#dea303";}
        $env = $this->get_env();
        $display = $env == "DEVELOPMENT" || $other['core'] == "view";

        if(!empty($other['raw']))
            foreach ($other['raw'] as $k => $r){
                $this->convertRaw($r,$k,$body);
            }

        $stack = "<div style='padding-left: 10px'>";
        $stack_raw = "";

        foreach ($other['stack'] as $k => $v){
            if(!isset($v['file']) && !isset($v['line']))
                continue;
            $k++;
            $last_file = explode("/",$v['file']);
            $last_file = end($last_file);
            $stack .= <<<STACK
                <div style="color: #fff; padding-left: 20px">
                    <div>#$k: {$v['function']}(...)</div>
                    <div><b>$last_file ({$v['line']})</b></div>
                    <span style="white-space: nowrap; word-break: keep-all">{$v['file']}; <b>{$v['line']}</b></span>
                    <hr>
                </div>
            STACK;
            $stack_raw .= <<<STACK
              -#$k: {$v['function']} {$v['file']}; {$v['line']}
            
            STACK;
        }

        $stack .="</div>";

        if($display) {
            echo <<<DEBUG
            <div style='background:#1d2124;padding:5px;color:#fffffa;overflow:auto;'>
                <h3 style='text-transform: uppercase; color: $title_color; margin: 2px 0'> $title </h3>
                <div style='color: $body_color; font-weight: bold; margin: 5px 0;'> $body </div><br>
                <div><b style="color: #dea303">$env ENVIRONMENT</b></div>
                <div>$stack</div>
            </div>
            DEBUG;
            return $other['act'] ?? "kill";
        }
        else{
            $dir = AutoLoader::get_root_dir() . "Lay" . DIRECTORY_SEPARATOR . "error_logs";
            $file_log = $dir . DIRECTORY_SEPARATOR . "lay_error.log";
            if(!is_dir($dir)) mkdir($dir,0755);

            if(!file_exists($file_log))
                $fh = fopen($file_log,"w+");
            elseif(filesize($file_log) > 2528576) {
                $i = 0;
                $logs = scandir($dir);
                $last_log = end($logs);
                $x = explode(".",$last_log);

                if(end($x) == "log")
                    $i = ((int) explode("g",$x[0])[1] ?? 0) + 1;

                $fh = fopen($dir . DIRECTORY_SEPARATOR . "lay_error$i.log", "w+");
            }
            else {
                $fh = fopen($file_log,"r+");
                fseek($fh,0,SEEK_END);
            }
            $body = strip_tags($body);
            $date = date("Y-m-d H:i:s e");
            fwrite($fh,<<<DEBUG
            [$date] $title: $body
            $stack_raw
            DEBUG) or die("Unable to write SQL error log in location " . $dir . " <br> Refer to " . __FILE__ . ": " . __LINE__);
            fclose($fh);
            echo "<b>Your attention is needed at the backend, check your Lay error logs for details</b>";
            return $other['act'] ?? "allow";
        }
    }
    private function convertRaw($print_val,$replace,&$body) : void {
        ob_start();
        print_r($print_val);
        echo " <i>(" . gettype($print_val) . ")</i>";
        $x = ob_get_clean();
        $x = empty($x) ? "NO VALUE PASSED" : $x;
        $x = "<span style='margin: 10px 0 1px; color: #65fad8'>$x</span>";
        $body = str_replace($replace, $x, $body);
    }
    protected function show_exception($type, $opt = []) : void {
        $query = $opt[0] ?? "";
        $query_type = $opt[1] ?? "";
        $trace = $opt['trace'] ?? true;
        $query = (self::$ENV == "DEVELOPMENT" && is_string($query)) ? htmlspecialchars($query) : $query;
        $dbg = $trace ? debug_backtrace(2) : [];

        #### SQR = Structured Query Review  &&&   SQE = Structured Query Error
        switch ($type){
            default:
                $act = $this->container("SQL Err","<b style='color: #008dc5'>".mysqli_error(SQL::instance()->get_link())."</b> <div style='margin: 10px 0'>Statement: __RAW_VALUE_TYPE__</div>", ["stack"=>$dbg,"core"=>"error","raw"=>["__RAW_VALUE_TYPE__" => $query_type],"act"=>"kill"]);
            break;
            case -9:
                $act = $this->container("SQL Review","<pre style='color: #dea303 !important'>$query</pre>",["stack"=>$dbg,"core"=>"view"]);
                break;
            case -8:$act = $this->container($opt['title'],$opt['body_includes'],["stack"=>$dbg,"core"=>"error","act" => @$opt['kill'] ? "kill" : "allow", "raw" => $opt['raw']]);break;

            case 0:
                $act = $this->container("Conn Err","No connection detected: <h5 style='color: #008dc5'>Connection might be closed:</h5>",["stack"=>$dbg,"core"=>"error"]);
                break;
            case 1:
                $db = $opt[0]; $usr = $opt[1]; $host = $opt[2];
                $act = $this->container("Conn Test", "<h2>Connection Established!</h2><u>Your connection info states:</u><div style='color: gold; font-weight: bold; margin: 5px 1px;'>&gt; Host: <u>".$host."</u></div><div style='color: gold; font-weight: bold; margin: 5px 1px;'>&gt; User: <u>".$usr."</u></div><div style='color: gold; font-weight: bold; margin: 5px 1px;'>&gt; Database: <u>".$db."</u></div>", ["stack"=>$dbg,"core"=>"success"]);
                break;
            case 2:
                $act = $this->container("Conn Err","<div style='color: #e00; font-weight: bold; margin: 5px 1px;'>".mysqli_connect_error()."</div>",["stack"=>$dbg,"core"=>"error","act"=>"kill"]);
                break;
            case 3:
                $act = $this->container("Conn Err","<div style='color: #e00; font-weight: bold; margin: 5px 1px;'>Failed to close connection. No pre-existing DB connection</div>",["stack"=>$dbg,"core"=>"error","act" => "kill"]);
                break;
        }
        if($act == "kill") die;
    }
}