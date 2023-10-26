<?php
declare(strict_types=1);
namespace Lay\core\traits;
use JetBrains\PhpStorm\ExpectedValues;
use Lay\core\Exception;
use Lay\libs\LayObject;

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

        self::$client = LayObject::instance()->to_object($obj);
    }
    protected static function set_internal_res_server(string $dir) : void {

        $slash = DIRECTORY_SEPARATOR;
        $root_server    = $dir  . "res" . $slash . "server" . $slash;
        $obj = [
            "root"    =>   $dir,
            "dir"     =>   $dir,
            "temp"    =>   $dir             . ".lay_temp"   . $slash,
            "lay"     =>   $dir             . "Lay"         . $slash,
            "lay_env" =>   $root_server     . "includes"    . $slash . "__env" . $slash,
            "db"      =>   $root_server     . "includes"    . $slash . "__env" . $slash . "__db" . $slash,
            "inc"     =>   $root_server     . "includes"    . $slash,
            "ctrl"    =>   $root_server     . "controller"  . $slash,
            "view"    =>   $root_server     . "view"        . $slash,
            "upload"  =>   "res" . $slash . "uploads" . $slash,
        ];

        self::$server = LayObject::instance()->to_object($obj);
    }
    protected static function set_internal_site_data(array $options) : void {
        $obj = array_merge([
            "author" => $options['author'] ?? null,
            "copy" => $options['copy'] ?? null,
            "name" => $options['name'] ?? null,
            "img" => [
                "logo" => isset(self::$client) ? self::$client->custom->img . "logo.png" : null,
                "favicon" => isset(self::$client) ? self::$client->custom->img . "favicon.png" : null,
                "icon" => isset(self::$client) ? self::$client->custom->img . "icon.png" : null,
            ],
            "color" => $options['color'] ?? null,
            "mail" => [
                ...$options['mail'] ?? []
            ],
            "tel" => $options['tel'] ?? null,
            "others" => $options['others'] ?? null,
        ], $options );


        self::$site = LayObject::instance()->to_object($obj);
    }

    private static function get_res(string $obj_type, $resource, string ...$index_chain) : mixed {
        foreach ($index_chain as $v) {
            if($resource === null)
                Exception::throw_exception("[$v] doesn't exist in the [res_$obj_type] chain", $obj_type);

            if($v === '' || is_null($v))
                continue;

            if(is_object($resource)) {
                $resource = $resource->{$v};
                continue;
            }

            if(is_array($resource)){
                $resource = $resource[$v];
            }

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
            Exception::throw_exception(
                "The index [$index[0]] being accessed may not exist or is forbidden.
                You can only access these index: " . implode(", ",$accepted_index),"Invalid Index"
            );

        $value = end($index);
        array_pop($index);

        $object_push = function (&$key) use ($value) {
            $key = str_replace(
                [ "@back","@front","@custom" ],
                [ self::$client->back->root, self::$client->front->root, self::$client->custom->root],
                $value
            );
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

    public static function set_res__client(
        #[ExpectedValues(["back","front","upload"])] string $client_index,
        mixed ...$chain_and_value
    ) : void {
        self::is_init();
        self::set_res(self::$client,["back","front","upload"], $client_index, ...$chain_and_value);
    }
    public function get_res__client(
        #[ExpectedValues(['api', 'lay', 'upload', 'custom', 'front', 'back'])] string $client_index = "",
        string ...$index_chain
    ) : mixed {
        self::is_init();
        return self::get_res("client", self::$client, $client_index, ...$index_chain);
    }

    public static function res_client() : object
    {
        return self::$client;
    }

    public static function set_res__server(
        #[ExpectedValues(["view","ctrl","inc","upload"])] string $client_index,
        string ...$chain_and_value
    ) : void {
        self::is_init();
        self::set_res(self::$server, ["view","ctrl","inc","upload"], $client_index, ...$chain_and_value);
    }

    /**
     * @param string $server_index
     * @param string ...$index_chain
     * @see set_internal_res_server
     * @return mixed
     */
    public function get_res__server(
        #[ExpectedValues(["root","dir","lay","lay_env","db","inc","ctrl","view","upload"])] string $server_index = "",
        string ...$index_chain
    )
    : mixed {
        self::is_init();
        return self::get_res("server", self::$server, $server_index, ...$index_chain);
    }

    /**
     * @see set_internal_res_server
     * @return object
     */
    public static function res_server() : object
    {
        if(!isset(self::$server)){
            self::set_dir();
            self::set_internal_res_server(self::$dir);
        }

        return self::$server;
    }

    public static function set_site_data(string $data_index, mixed ...$chain_and_value) : void {
        self::is_init();

        self::set_res(self::$site, [], $data_index, ...$chain_and_value);
    }

    /**
     * @param string $data_index
     * @param string ...$index_chain
     * @see set_internal_site_data
     * @return mixed
     */
    public function get_site_data(string $data_index = "", string ...$index_chain) : mixed {
        self::is_init(true);
        return self::get_res("site_data", self::$site, $data_index, ...$index_chain);
    }

    /**
     * @see set_internal_site_data
     * @return object
     */
    public static function site_data() : object
    {
        self::is_init(true);
        return self::$site;
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
