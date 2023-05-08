<?php
declare(strict_types=1);
namespace Lay\core\sockets;
use Lay\core\Exception;
use Lay\libs\ObjectHandler;

trait Resources {
    private static object $client;
    private static object $server;
    private static object $site;

    private static string $CLIENT_VALUES = "";
    ///### Assets Resource and Page Metadata
    protected static function set_internal_res_client(string $base, string $env_src) : void {
        $root_client    = $base . "res/client/";
        $custom = $env_src . "/custom/";
        $front = $env_src . "/front/";
        $back = $env_src . "/back/";

        $obj = [
            "api"    =>     $base . "api/",
            "lay"    =>     $base . "Lay/",
            "upload" =>     $base . "res/uploads/",
            "custom"  => [
                "root"      =>     $root_client . $custom,
                "img"       =>     $root_client . $custom . "images/",
                "css"       =>     $root_client . $custom . "css/",
                "js"        =>     $root_client . $custom . "js/",
                "plugin"    =>     $root_client . $custom . "plugin/",
            ],
            "front"   =>   [
                "root"      =>     $root_client . $front,
                "img"       =>     $root_client . $front . "images/",
                "css"       =>     $root_client . $front . "css/",
                "js"        =>     $root_client . $front . "js/",
            ],
            "back"   =>   [
                "root"  =>         $root_client . $back,
                "img"   =>         $root_client . $back . "images/",
                "css"   =>         $root_client . $back . "css/",
                "js"    =>         $root_client . $back . "js/",
            ],
        ];

        self::$client = ObjectHandler::instance()->to_object($obj);
    }
    protected static function set_internal_res_server(string $dir) : void {
        $slash = DIRECTORY_SEPARATOR;
        $root_server    = $dir  . "res" . $slash . "server" . $slash;
        $obj = [
            "dir"     =>   $dir,
            "inc"     =>   $root_server     . "includes"    . $slash,
            "ctrl"    =>   $root_server     . "controller"  . $slash,
            "view"    =>   $root_server     . "view"        . $slash,
            "upload"  =>   "res"            . $slash . "uploads" . $slash,
        ];

        self::$server = ObjectHandler::instance()->to_object($obj);
    }
    protected static function set_internal_site_data(array $options) : void {
        $obj = array_merge([
            "author" => $options['author'],
            "copy" => $options['copy'],
            "name" => $options['name'],
            "img" => [
                "logo" => self::$client->custom->img . "logo.png",
                "favicon" => self::$client->custom->img . "favicon.png",
                "icon" => self::$client->custom->img . "icon.png",
            ],
            "color" => $options['color'],
            "mail" => [
                ...$options['mail']
            ],
            "tel" => $options['tel'],
            "others" => $options['others'],
        ], $options );

        self::$site = ObjectHandler::instance()->to_object($obj);
    }

    private static function get_res(string $obj_type, $resource, string ...$index_chain) {

        foreach ($index_chain as $v) {
            $resource = @$resource->{$v};

            if($resource === null)
                Exception::throw_exception("[$v] doesn't exist in the [res_$obj_type] chain",$obj_type);
        }
        return $resource;
    }

    /**
     * @param object $resource
     * @param string $index
     * @param array $accepted_index
     * @return void
     * @throws \Exception
     */
    private static function set_res(object &$resource, array $accepted_index = [], ...$index) : void {
        if(!empty($accepted_index) && !in_array($index[0],$accepted_index,true))
            Exception::throw_exception("The index [$index[0]] being accessed may not exist or is forbidden.
                You can only access these index: " . implode(", ",$accepted_index),"Invalid Index");

        $value = end($index);
        array_pop($index);

        $object_push = function (&$key) use ($value) {
            $key = $value;
        };
        switch (count($index)){
            default:
                $object_push($resource->{$index[0]});
                break;
            case 2:
                $object_push($resource->{$index[0]}->{$index[1]});
                break;
            case 3:
                $object_push($resource->{$index[0]}->{$index[1]}->{$index[2]});
                break;
            case 4:
                $object_push($resource->{$index[0]}->{$index[1]}->{$index[2]}->{$index[3]});
                break;
            case 5:
                $object_push($resource->{$index[0]}->{$index[1]}->{$index[2]}->{$index[3]}->{$index[4]});
                break;
            case 6:
                $object_push($resource->{$index[0]}->{$index[1]}->{$index[2]}->{$index[3]}->{$index[4]}->{$index[5]});
                break;
        }
    }

    # Client Side
    public static function set_res__client(...$index__and__value) : void {
        self::is_init();
        self::set_res(self::$client,["back","front","upload"],...$index__and__value);
    }
    public function get_res__client(string ...$index_chain) {
        self::is_init();
        return self::get_res("client", self::$client,...$index_chain);
    }

    # Server Side
    public static function set_res__server(...$index__and__value) : void {
        self::is_init();
        self::set_res(self::$server,["view","ctrl","inc","upload",],...$index__and__value);
    }
    public function get_res__server(string ...$index_chain) {
        self::is_init();
        return self::get_res("server", self::$server,...$index_chain);
    }

    # Site Metadata
    public static function set_site_data(...$index__and__value) : void {
        self::is_init();
        self::set_res(self::$site,[],...$index__and__value);
    }
    public function get_site_data(string ...$index_chain) {
        self::is_init();
        return self::get_res("site_data", self::$site,...$index_chain);
    }

    public function send_to_client(array $values) : string {
        self::is_init();
        foreach ($values as $v){
            self::$CLIENT_VALUES .= $v;
        }

        return self::$CLIENT_VALUES;
    }

    public function get_client_values() : string {
        self::is_init();
        return self::$CLIENT_VALUES;
    }
}