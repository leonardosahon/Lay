<?php
namespace Lay\core\traits;
/**
 * Singleton Implementation
 */
trait IsSingleton {
    protected static self $instance;
    private final function __construct(){}
    private function __clone(){}
    public final static function instance() : self {
        if(!isset(self::$instance))
            self::$instance = new self();
        return self::$instance;
    }

    public static function new() : self {
        return self::instance();
    }
}