<?php

namespace Lay\libs;

abstract class CSV {
    protected static function resolve(int $code, string $message, ?array $data = null) : array {
        return [
            "code" => $code,
            "msg" => $message,
            "data" => $data
        ];
    }

    public static function process(string $file, \Closure $callback, int $max_size_kb = 1000) : array {
        $file_type = mime_content_type($file);
        $max_size_kb = $max_size_kb /1000;

        if((filesize($file)/1000) > $max_size_kb)
            return self::resolve(0, "Max file size of [{$max_size_kb}kb] exceeded");


        if(!$file_type)
            return self::resolve(0, "Invalid file received");

        if(!in_array($file_type, ["text/csv" , "text/plain"], true))
            return self::resolve(0, "Invalid file type received, ensure your file is saved as <b>CSV</b>");

        $fh = fopen($file,'r');
        $output = "";

        while ($row = fgetcsv($fh)){
            $x = $callback($row);
            
            if(is_array($x))
                return $x;
            
            $output .= $x;
        }

        return self::resolve(1, "Processed successfully", [$output]);
    }
}