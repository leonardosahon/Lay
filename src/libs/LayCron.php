<?php

namespace Lay\libs;

use JetBrains\PhpStorm\ExpectedValues;
use Lay\core\Exception;
use Lay\core\LayConfig;

final class LayCron {
    private const CRON_FILE = "/tmp/crontab.txt";
    private const DB_SCHEMA = [
        "mailto" => "",
        "jobs" => [],
    ];

    private string $output_file;
    private array $exec_output = [
        "exec" => true,
        "msg" => "Execution successful"
    ];
    private bool $save_job_output = false;
    private array $jobs_list;
    private string $report_email;
    private string $job_id;
    private string $minute = "*"; // (0 - 59)
    private string $hour = "*"; // (0 - 23)
    private string $day_of_the_month = "*"; // (1 - 31)
    private string $month = "*"; // (1 - 12)
    private string $day_of_the_week = "*"; // (0 - 6) (Sunday=0 or 7)

    private function cron_db() : string {
        $dir = LayConfig::mk_tmp_dir();
        $file = $dir . "cron_jobs.json";
        $this->output_file = $dir . "cron_outputs.txt";

        if(!file_exists($file))
            file_put_contents($file, json_encode(self::DB_SCHEMA));

        if(!file_exists($this->output_file))
            file_put_contents($this->output_file, '***** LAY CRON JOBS OUTPUT *****' . PHP_EOL);

        return $file;
    }

    private function db_data_init() : void {
        if(isset($this->jobs_list) && isset($this->report_email))
            return;

        $data = json_decode(file_get_contents($this->cron_db()), true);

        $this->jobs_list = $this->jobs_list ?? $data['jobs'] ?? [];
        $this->report_email = $this->report_email ?? $data['mailto'] ?? "";
    }

    private function db_data_clear_all() : void {
        $data = self::DB_SCHEMA;

        $this->jobs_list = $data['jobs'];
        $this->report_email = $data['mailto'];

        $this->commit();
    }

    private function db_job_by_id(string|int $uid) : ?string {
        $this->db_data_init();
        return $this->jobs_list[$uid] ?? null;
    }

    private function db_job_all() : ?array {
        $this->db_data_init();
        return $this->jobs_list;
    }

    private function db_job_exists(string $job) : array {
        return LayArraySearch::run($job, $this->db_job_all());
    }

    private function db_email_exists(?string $email = null) : ?string {
        $email = $email ?? $this->report_email;
        return $email == $this->db_get_email();
    }

    private function db_get_email() : ?string {
        $this->db_data_init();
        return $this->report_email;
    }

    private function db_data_save() : bool {
        $data = self::DB_SCHEMA;
        $data['jobs'] = $this->jobs_list;
        $data['mailto'] = $this->report_email;

        return (bool) file_put_contents($this->cron_db(), json_encode($data));
    }

    private function crontab_save() : bool {
        $mailto = $this->report_email ? 'MAILTO=' . $this->report_email : 'MAILTO=""';
        $mailto .= PHP_EOL;
        $cron_jobs = implode("", $this->jobs_list);

        file_put_contents(self::CRON_FILE, $mailto . $cron_jobs);

        $install = exec("crontab " . self::CRON_FILE . " 2>&1", $out);
        $exec = !str_contains($install, "errors in crontab file, can't install");

        $this->exec_output = [
            "exec" => $exec,
            "msg" => !empty($out) ? implode("\n", $out) : "Cron job added successfully"
        ];

        return $exec;
    }

    private function commit() : bool {
        return $this->crontab_save() && $this->db_data_save();
    }

    private function make_job(string $job) : string {
        $this->db_data_init();

        $server = LayConfig::res_server();

        $schedule = $this->minute . " " . $this->hour . " " . $this->day_of_the_month . " " . $this->month . " " . $this->day_of_the_week;

        $job_plain = $job;
        $job = $server->root . $job_plain;
        $job = " /usr/bin/php $job";

        if($this->save_job_output) {
            $job = ' out="$(' . $job . ')";';
            $job .= ' echo "' . $job_plain . ': $out "';
            $job .= " >> " . $this->output_file;
        }

        return $schedule . $job . PHP_EOL;
    }

    private function add_job(string $job) : void {
        $add = str_contains(shell_exec("crontab -l 2>&1"), "no crontab for");

        if(!$add && !$this->db_job_exists($job)['found']) {
            if(isset($this->job_id))
                $this->jobs_list[$this->job_id] = $job;
            else
                $this->jobs_list[] = $job;

            $add = true;
        }

        if(!$add && $this->db_email_exists())
            return;

        $this->commit();
    }

    private function delete_job_by_id(string|int $uid) : bool {
        $this->db_data_init();

        $existed = isset($this->jobs_list[$uid]);

        if(!$existed)
            return true;

        unset($this->jobs_list[$uid]);
        return $this->commit();
    }

    private function delete_job_by_job(string $job) : bool {
        $this->db_data_init();

        $job = $this->make_job($job);
        $job = $this->db_job_exists($job);

        if(!$job['found'])
            return true;

        unset($this->jobs_list[$job['index'][0]]);
        return $this->commit();
    }

    private function handle_ranges_and_more(string $input, string $date_format) : string {
        $output = "";

        foreach (explode(",", $input) as $int) {
            if(str_contains($int, "-")) {
                $range = explode("-", $int);
                $res = date($date_format, strtotime($range[0])) . "-" . date($date_format, strtotime($range[1]));
            }
            else
                $res = date($date_format, strtotime($int));

            $res .= ",";

            if(str_contains($output, $res))
                continue;

            $output .= $res;
        }

        return rtrim($output, ",");
    }

    public static function new () : self {
        return new self();
    }

    public function job_id(string $uid) : self {
        $this->job_id = $uid;
        return $this;
    }

    public function log_output() : self {
        $this->save_job_output = true;
        return $this;
    }

    public function new_job(string $job) : array {
        $this->add_job($this->make_job($job));
        return $this->exec_output;
    }

    public function print_job(string $job) : void {
        echo $this->make_job($job) . '<br/>';
    }

    public function exec(string $command) : array {
        $this->add_job($command . PHP_EOL);
        return $this->exec_output;
    }

    public function report_email(string $email) : self {
        $this->report_email = $email;
        return $this;
    }

    public function list_jobs() : ?array {
        return $this->db_job_all();
    }

    public function get_job(string|int $uid) : ?string {
        return $this->db_job_by_id($uid);
    }

    public function get_crontab() : string {
        if(!file_exists(self::CRON_FILE))
            return "";

        return file_get_contents(self::CRON_FILE);
    }

    public function unset(string|int $uid_or_job) : bool {
        return $this->delete_job_by_id($uid_or_job) || $this->delete_job_by_job($uid_or_job);
    }

    public function unset_report_email() : void {
        $this->report_email = "";
        $this->commit();
    }

    public function clear_all() : void {
        $this->db_data_clear_all();
    }

    public function raw_schedule(?string $minute = null, ?string $hour = null, ?string $day_of_the_month = null, ?string $month = null, ?string $day_of_the_week = null) : self {
        $this->minute = $minute ?? $this->minute;
        $this->hour = $hour ?? $this->hour;
        $this->day_of_the_month = $day_of_the_month ?? $this->day_of_the_month;
        $this->month = $month ?? $this->month;
        $this->day_of_the_week = $day_of_the_week == '7' ? '0' : ($day_of_the_week ?? $this->day_of_the_week);

        return $this;
    }

    /**
     * Schedules jobs for every number of minutes indicated.
     * @param int $minute
     * @example `5` minutes = every `5` minutes. i.e 5, 10, 15...n
     * @return $this
     */
    public function every_minute(int $minute = 1) : self {
        $this->raw_schedule(minute: "*/$minute",);
        return $this;
    }

    /**
     * Schedules jobs for every number of hours indicated.
     * @param int $hour
     * @example `2` hour = every `2` hours. i.e 2, 4, 6, 8...n
     * @return $this
     */
    public function every_hour(int $hour = 1) : self {
        $this->raw_schedule(hour: "*/$hour");
        return $this;
    }

    /**
     * 12-hours of every day.
     * Not to be mistaken for `every_hour` `every_minute`.
     * This method schedules the job for the specified `$hour:$minute am|pm` of every day;
     * except days are modified by the `weekly` method.
     * @param int $hour
     * @param int $minute
     * @param bool $am
     * @return $this
     */
    public function daily(int $hour = 12, int $minute = 0, bool $am = true) : self {
        $am = $am ? "am" : "pm";
        $date = explode(" ", date("G i", strtotime("$hour:$minute{$am}")));
        $this->raw_schedule(minute: $date[1], hour: $date[0]);
        return $this;
    }

    /**
     * Schedules for all the days specified.
     * To tweak the time, you need to call the appropriate methods when building your job.
     * @param string $day_of_the_week accepts: mon, monday, Monday;
     * it could be a range or comma-separated values.
     * @return $this
     * @example `->weekly('mon - fri, sun')`
     */
    public function weekly(string $day_of_the_week) : self {
        $this->raw_schedule(day_of_the_week: $this->handle_ranges_and_more($day_of_the_week, "w"));
        return $this;
    }

    /**
     * Schedules for specified days of every month.
     * To tweak the day and time, you need to call the appropriate methods when building your job.
     * @param string|int $days_of_the_month accepts: 1 - 31;
     * it could be an int, a range or comma-separated values.
     * @return $this
     * @throws \Exception
     */
    public function monthly(string|int $days_of_the_month = 1) : self {
        if(!is_int($days_of_the_month)) {
            $this->raw_schedule(day_of_the_month: $this->handle_ranges_and_more($days_of_the_month, "j"));
            return $this;
        }

        if ($days_of_the_month > 31)
            Exception::throw_exception("Argument #1: Day of the month cannot be greater than 31", "CronBoundExceeded");

        if ($days_of_the_month < 1)
            Exception::throw_exception("Argument #1: Day of the month cannot be less than 1", "CronBoundExceeded");

        $this->raw_schedule(day_of_the_month: $days_of_the_month);
        return $this;
    }

    /**
     * Schedules for all the months specified.
     * To tweak the day, month, and time, you need to call the appropriate methods when building your job.
     * @param string $months accepts: Jan, jan, January
     * it could be a range or comma-separated values.
     * @return $this
     */
    public function yearly(string $months) : self {
        $this->raw_schedule(month: $this->handle_ranges_and_more($months, "n"));
        return $this;
    }

}