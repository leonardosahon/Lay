<?php
declare(strict_types=1);
namespace Lay\core\traits;
use Dotenv\Dotenv;
use JetBrains\PhpStorm\ExpectedValues;
use Lay\core\Exception;
use Lay\libs\LayObject;

trait Resources {
    private static object $client;
    private static object $server;
    private static object $site;

    private static bool $env_loaded = false;

    private static string $CLIENT_VALUES = "";
    ///### Assets Resource and Page Metadata
    protected static function set_internal_res_client(string $base, string $env_src) : void {
        $root_client = $base . "res/client/";
        $custom = $env_src . "/custom/";
        $front = $env_src . "/front/";
        $back = $env_src . "/back/";

        $obj = new \stdClass();

        $custom_2d = new \stdClass();
        $custom_2d->root = $root_client     . $custom;
        $custom_2d->img = $root_client      . $custom . "images/";
        $custom_2d->css = $root_client      . $custom . "css/";
        $custom_2d->js = $root_client       . $custom . "js/";
        $custom_2d->plugin = $root_client   . $custom . "plugin/";

        $front_2d = new \stdClass();
        $front_2d->root = $root_client      . $front;
        $front_2d->img = $root_client       . $front . "images/";
        $front_2d->css = $root_client       . $front . "css/";
        $front_2d->js = $root_client        . $front . "js/";

        $back_2d = new \stdClass();
        $back_2d->root = $root_client       . $back;
        $back_2d->img = $root_client        . $back . "images/";
        $back_2d->css = $root_client        . $back . "css/";
        $back_2d->js = $root_client         . $back . "js/";

        $obj->api = $base . "api/";
        $obj->lay = $base . "Lay/";
        $obj->upload = $base . "res/uploads/";
        $obj->root = $root_client . $env_src;
        $obj->custom = $custom_2d;
        $obj->front = $front_2d;
        $obj->back = $back_2d;

        self::$client = $obj;
    }
    protected static function set_internal_res_server(string $dir) : void {

        $slash = DIRECTORY_SEPARATOR;
        $root_server    = $dir  . "res" . $slash . "server" . $slash;

        $obj = new \stdClass();

        $obj->root    =   $dir;
        $obj->dir     =   $dir;
        $obj->temp    =   $dir             . ".lay_temp"   . $slash;
        $obj->lay     =   $dir             . "Lay"         . $slash;
        $obj->lay_env =   $root_server     . "includes"    . $slash . "__env" . $slash;
        $obj->inc     =   $root_server     . "includes"    . $slash;
        $obj->ctrl    =   $root_server     . "controller"  . $slash;
        $obj->view    =   $root_server     . "view"        . $slash;
        $obj->upload  =   "res" . $slash . "uploads" . $slash;

        self::$server = $obj;
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
                "meta" => isset(self::$client) ? self::$client->custom->img . "meta.png" : null,
            ],
            "color" => $options['color'] ?? null,
            "mail" => [
                ...$options['mail'] ?? []
            ],
            "tel" => $options['tel'] ?? null,
            "others" => $options['others'] ?? null,
        ], $options );


        self::$site = LayObject::new()->to_object($obj);
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
                [ rtrim(self::$client->back->root,"/"), rtrim(self::$client->front->root,"/"), rtrim(self::$client->custom->root,"/")],
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

    public static function mk_tmp_dir () : string {
        $dir = self::res_server()->temp;

        if(!is_dir($dir)) {
            umask(0);
            mkdir($dir, 0755, true);
        }

        return $dir;
    }

    public static function load_env() : void {
        if(self::$env_loaded)
            return;

        Dotenv::createImmutable(self::res_server()->lay_env)->load();
    }
}
