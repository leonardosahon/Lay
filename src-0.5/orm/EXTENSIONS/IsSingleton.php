<?php
namespace Lay\orm\EXTENSIONS;
/**
 * Singleton Implementation
 */
trait IsSingleton {
    protected static self $instance;
    private function __construct(){}
    private function __clone(){}
    public static function instance() : self {
        if(!isset(self::$instance))
            self::$instance = new self();
        return self::$instance;
    }
}