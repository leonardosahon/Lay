<?php
declare(strict_types=1);
namespace Lay\core\sockets;
use Lay\core\Exception;

trait Resources {
    ///### Assets Resource and Page Metadata
    private static function get_res($resource, string ...$index_chain) {
        foreach ($index_chain as $v){
            $resource = $resource->{$v};
        }
        return $resource;
    }

    /**
     * @param object $resource
     * @param string $index
     * @param array $accepted_index
     * @return void
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
        }
    }

    # Client Side
    public static function set_res__client(...$index__and__value) : void {
        self::is_init();
        self::set_res(self::$client,["back","front","upload"],...$index__and__value);
    }
    public function get_res__client(string ...$index_chain) {
        self::is_init();
        return self::get_res(self::$client,...$index_chain);
    }

    # Server Side
    public static function set_res__server(...$index__and__value) : void {
        self::is_init();
        self::set_res(self::$server,["view","ctrl","inc","upload",],...$index__and__value);
    }
    public function get_res__server(string ...$index_chain) {
        self::is_init();
        return self::get_res(self::$server,...$index_chain);
    }

    # Site Metadata
    public static function set_site_data(...$index__and__value) : void {
        self::is_init();
        self::set_res(self::$site,[],...$index__and__value);
    }
    public function get_site_data(string ...$index_chain) {
        self::is_init();
        return self::get_res(self::$site,...$index_chain);
    }
}