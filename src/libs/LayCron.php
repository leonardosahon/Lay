<?php

namespace Lay\libs;

use Lay\core\LayConfig;
use Lay\core\sockets\IsSingleton;

final class LayCron {
    use IsSingleton;
    private const CRON_FILE = "/tmp/crontab.txt";
    private const DB_SCHEMA = [
        "mailto" => "",
        "jobs" => [],
    ];

    private static string $output_file;
    private static array $exec_output = [
        "exec" => true,
        "msg" => "Execution successful"
    ];
    private static bool $save_job_output = false;
    private static array $jobs_list;
    private static string $report_email;
    private static string $job_id;
    private static string $minute = "*"; // (0 - 59)
    private static string $hour = "*"; // (0 - 23)
    private static string $day_of_month = "*"; // (1 - 31)
    private static string $month = "*"; // (1 - 12) OR jan,feb,mar,apr ...
    private static string $day_of_week = "*"; // (0 - 6) (Sunday=0 or 7) OR sun,mon,tue,wed,thu,fri,sat

    private static function cron_db() : string {
        $dir = LayConfig::res_server()->temp;
        $file = $dir . "cron_jobs.json";
        self::$output_file = $dir . "cron_outputs.txt";

        if(!is_dir($dir)) {
            umask(0);
            mkdir($dir, 0777, true);
        }

        if(!file_exists($file))
            file_put_contents($file, json_encode(self::DB_SCHEMA));

        if(!file_exists(self::$output_file))
            file_put_contents(self::$output_file, '***** LAY CRON JOBS OUTPUT *****' . PHP_EOL);

        return $file;
    }

    private static function db_data_init() : void {
        if(isset(self::$jobs_list) && isset(self::$report_email))
            return;

        $data = json_decode(file_get_contents(self::cron_db()), true);

        self::$jobs_list = self::$jobs_list ?? $data['jobs'] ?? [];
        self::$report_email = self::$report_email ?? $data['mailto'] ?? "";
    }

    private static function db_data_clear_all() : void {
        $data = self::DB_SCHEMA;

        self::$jobs_list = $data['jobs'];
        self::$report_email = $data['mailto'];

        self::commit();
    }

    private static function db_job_by_id(string|int $uid) : ?string {
        self::db_data_init();
        return self::$jobs_list[$uid] ?? null;
    }

    private static function db_job_all() : ?array {
        self::db_data_init();
        return self::$jobs_list;
    }

    private static function db_job_exists(string $job) : array {
        return LayArraySearch::run($job, self::db_job_all());
    }

    private static function db_email_exists(?string $email = null) : ?string {
        $email = $email ?? self::$report_email;
        return $email == self::db_get_email();
    }

    private static function db_get_email() : ?string {
        self::db_data_init();
        return self::$report_email;
    }

    private static function db_data_save() : bool {
        $data = self::DB_SCHEMA;
        $data['jobs'] = self::$jobs_list;
        $data['mailto'] = self::$report_email;

        return (bool) file_put_contents(self::cron_db(), json_encode($data));
    }

    private static function crontab_save() : bool {
        $mailto = self::$report_email ? 'MAILTO=' . self::$report_email : 'MAILTO=""';
        $mailto .= PHP_EOL;
        $cron_jobs = implode("", self::$jobs_list);

        file_put_contents(self::CRON_FILE, $mailto . $cron_jobs);
        exec("crontab -r 2>&1", $out);

        $install = shell_exec("crontab " . self::CRON_FILE . " 2>&1");
        $exec = !str_contains($install, "errors in crontab file, can't install");

        self::$exec_output = [
            "exec" => $exec,
            "msg" => $install
        ];

        return $exec;
    }

    private static function commit() : bool {
        return self::crontab_save() && self::db_data_save();
    }

    private static function make_job(string $job) : string {
        self::db_data_init();

        $server = LayConfig::res_server();

        $schedule = self::$minute . " " . self::$hour . " " . self::$day_of_month . " " . self::$month . " " . self::$day_of_week;

        $job_plain = $job;
        $job = $server->root . $job_plain;
        $job = " /usr/bin/php $job";

        if(self::$save_job_output) {
            $job = ' out="$(' . $job . ')";';
            $job .= ' echo "' . $job_plain . ': $out "';
            $job .= " >> " . self::$output_file;
        }

        return $schedule . $job . PHP_EOL;
    }

    private static function add_job(string $job) : bool {
        $add = str_contains(shell_exec("crontab -l 2>&1"), "no crontab for");

        if(!$add && !self::db_job_exists($job)['found']) {
            if(isset(self::$job_id))
                self::$jobs_list[self::$job_id] = $job;
            else
                self::$jobs_list[] = $job;

            $add = true;
        }

        if(!$add && self::db_email_exists())
            return true;

        return self::commit();
    }

    private static function delete_job_by_id(string|int $uid) : bool {
        self::db_data_init();

        $existed = isset(self::$jobs_list[$uid]);

        if(!$existed)
            return true;

        unset(self::$jobs_list[$uid]);
        return self::commit();
    }

    private static function delete_job_by_job(string $job) : bool {
        self::db_data_init();

        $job = self::make_job($job);
        $job = self::db_job_exists($job);

        if(!$job['found'])
            return true;

        unset(self::$jobs_list[$job['index'][0]]);
        return self::commit();
    }

    public function job_id(string $uid) : self {
        self::$job_id = $uid;
        return $this;
    }

    public function log_output() : self {
        self::$save_job_output = true;
        return $this;
    }

    public function new_job(string $job) : array {
        self::add_job(self::make_job($job));
        return self::$exec_output;
    }

    public function exec(string $command) : array {
        self::add_job($command . PHP_EOL);
        return self::$exec_output;
    }

    public function report_email(string $email) : self {
        self::$report_email = $email;
        return $this;
    }

    public function list_jobs() : ?array {
        return self::db_job_all();
    }

    public function get_job(string|int $uid) : ?string {
        return self::db_job_by_id($uid);
    }

    public function get_crontab() : string {
        if(!file_exists(self::CRON_FILE))
            return "";

        return file_get_contents(self::CRON_FILE);
    }

    public function unset(string|int $uid_or_job) : bool {
        return self::delete_job_by_id($uid_or_job) || self::delete_job_by_job($uid_or_job);
    }

    public function unset_report_email() : void {
        self::$report_email = "";
        self::commit();
    }

    public function clear_all() : void {
        self::db_data_clear_all();
    }

    public function schedule(string $minute = "*", string $hour = "*", string $day_of_month = "*", string $month = "*", string $day_of_week = "*") : self {
        self::$minute = $minute;
        self::$hour = $hour;
        self::$day_of_month = $day_of_month;
        self::$month = $month;
        self::$day_of_week = $day_of_week;

        return $this;
    }

    public function daily(int $hour, int $minute, bool $am) : self {
        $am = $am ? "am" : "pm";
        $date = explode(" ", date("G i", strtotime("$hour:$minute{$am}")));
        $this->schedule(minute: $date[1], hour: $date[0]);
        return $this;
    }

    // TODO: Make this function flexible, so it can take next 5 minutes, next week, etc
    public function next(string $what) : self {
        $what = "next " . $what;
        $date = explode(" ", date("G i", strtotime($what)));

        $this->schedule(minute: $date[1], hour: $date[0]);
        return $this;
    }

}
