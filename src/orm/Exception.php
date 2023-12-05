<?php
declare(strict_types=1);

namespace Lay\orm;

use Lay\AutoLoader;
use Lay\core\LayConfig;

class Exception
{
    private static string $ENV = "DEVELOPMENT";

    public function set_env(string $ENV): void
    {
        $ENV = strtolower($ENV);
        self::$ENV = strtoupper(($ENV == "dev" || $ENV == "development") ? "development" : "production");
    }

    public function get_env(): string
    {
        return self::$ENV;
    }

    /**
     * @throws \Exception
     */
    public function use_exception(string $title, string $body, bool $kill = true, array $trace = [], array $raw = [], bool $use_lay_error = true): void
    {
        $this->show_exception(-8,
            [
                "title" => $title,
                "body_includes" => $body,
                "kill" => $kill,
                "trace" => $trace,
                "raw" => $raw,
                "use_lay_error" => $use_lay_error,
            ]
        );
    }

    private function container($title, $body, $other = []): string
    {
        if ($other['core'] == "error") {
            $title_color = "#ff0014";
            $body_color = "#ff5000";
        } elseif ($other['core'] == "success") {
            $title_color = "#1cff03";
            $body_color = "#1b8b07";
        } else {
            $title_color = "#5656f5";
            $body_color = "#dea303";
        }
        $env = $this->get_env();
        $display = $env == "DEVELOPMENT" || $other['core'] == "view";

        if (!empty($other['raw']))
            foreach ($other['raw'] as $k => $r) {
                $this->convertRaw($r, $k, $body);
            }

        $referer = $_SERVER['HTTP_REFERER'] ?? 'unknown';
        $ip = LayConfig::get_ip();

        $stack = "<div style='padding-left: 5px; color: #5656f5; margin: 5px 0'><b>Referrer:</b> <span style='color:#00ff80'>$referer</span> <br /> <b>IP:</b> <span style='color:#00ff80'>$ip</span></div><div style='padding-left: 10px'>";
        $stack_raw = <<<STACK
         REFERRER: $referer
         IP: $ip

        STACK;

        foreach ($other['stack'] as $k => $v) {
            if (!isset($v['file']) && !isset($v['line']))
                continue;

            $k++;
            $last_file = explode("/", $v['file']);
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

        $stack .= "</div>";

        if ($display) {
            echo <<<DEBUG
            <div style='background:#1d2124;padding:5px;color:#fffffa;overflow:auto;'>
                <h3 style='text-transform: uppercase; color: $title_color; margin: 2px 0'> $title </h3>
                <div style='color: $body_color; font-weight: bold; margin: 5px 0;'> $body </div><br>
                <div><b style="color: #dea303">$env ENVIRONMENT</b></div>
                <div>$stack</div>
            </div>
            DEBUG;
            return $other['act'] ?? "kill";
        } else {
            $dir = LayConfig::res_server()->temp;
            $file_log = $dir . DIRECTORY_SEPARATOR . "exceptions.log";

            if (!is_dir($dir)) {
                umask(0);
                mkdir($dir, 0755, true);
            }

            $date = date("Y-m-d H:i:s e");
            $body = strip_tags($body);
            $body = <<<DEBUG
            [$date] $title: $body
            $stack_raw
            DEBUG;

            file_put_contents($file_log, $body, FILE_APPEND);

            echo "<b>Your attention is needed at the backend, check your Lay error logs for details</b>";
            return $other['act'] ?? "allow";
        }
    }

    private function convertRaw($print_val, $replace, &$body): void
    {
        ob_start();
        print_r($print_val);
        echo " <i>(" . gettype($print_val) . ")</i>";
        $x = ob_get_clean();
        $x = empty($x) ? "NO VALUE PASSED" : $x;
        $x = "<span style='margin: 10px 0 1px; color: #65fad8'>$x</span>";
        $body = str_replace($replace, $x, $body);
    }

    /**
     * @throws \Exception
     */
    protected function show_exception($type, $opt = []): void
    {
        $query = $opt[0] ?? "";
        $query_type = $opt[1] ?? "";
        $use_lay_error = $opt['use_lay_error'] ?? true;
        $query = (self::$ENV == "DEVELOPMENT" && is_string($query)) ? htmlspecialchars($query) : $query;
        $trace = [...$opt['trace'] ?? [], ...debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)];

        if (!$use_lay_error) {
            if($opt['kill'] ?? true) {
                $exception_class = str_replace(" ", "", ucwords($opt['title']));

                if(!class_exists($exception_class)) {
                    $anon_class = new class extends \Exception {};
                    class_alias(get_class($anon_class), $exception_class);
                }

                throw new $exception_class($opt['body_includes'], $type);
            }

            return;
        }

        switch ($type) {
            default:
                $act = $this->container("QueryExecErr", "<b style='color: #008dc5'>" . mysqli_error(SQL::new()->get_link()) . "</b> <div style='color: #fff0b3; margin-top: 5px'>$query</div> <div style='margin: 10px 0'>Statement: __RAW_VALUE_TYPE__</div>", ["stack" => $trace, "core" => "error", "raw" => ["__RAW_VALUE_TYPE__" => $query_type], "act" => "kill"]);
                break;
            case -9:
                $act = $this->container("QueryReview", "<pre style='color: #dea303 !important'>$query</pre>", ["stack" => $trace, "core" => "view"]);
                break;
            case -8:
                $act = $this->container($opt['title'], $opt['body_includes'], ["stack" => $trace, "core" => "error", "act" => @$opt['kill'] ? "kill" : "allow", "raw" => $opt['raw']]);
                break;

            case 0:
                $act = $this->container("ConnErr", "No connection detected: <h5 style='color: #008dc5'>Connection might be closed:</h5>", ["stack" => $trace, "core" => "error"]);
                break;
            case 1:
                $db = $opt[0];
                $usr = $opt[1];
                $host = $opt[2];
                $act = $this->container("ConnTest", "<h2>Connection Established!</h2><u>Your connection info states:</u><div style='color: gold; font-weight: bold; margin: 5px 1px;'>&gt; Host: <u>" . $host . "</u></div><div style='color: gold; font-weight: bold; margin: 5px 1px;'>&gt; User: <u>" . $usr . "</u></div><div style='color: gold; font-weight: bold; margin: 5px 1px;'>&gt; Database: <u>" . $db . "</u></div>", ["stack" => $trace, "core" => "success"]);
                break;
            case 2:
                $act = $this->container("ConnErr", "<div style='color: #e00; font-weight: bold; margin: 5px 1px;'>" . mysqli_connect_error() . "</div>", ["stack" => $trace, "core" => "error", "act" => "kill"]);
                break;
            case 3:
                $act = $this->container("ConnErr", "<div style='color: #e00; font-weight: bold; margin: 5px 1px;'>Failed to close connection. No pre-existing DB connection</div>", ["stack" => $trace, "core" => "error", "act" => "kill"]);
                break;
        }

        http_response_code(500);
        if ($act == "kill")
            die;
    }
}

