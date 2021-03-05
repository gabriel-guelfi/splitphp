<?php

class ObjLoader {

    // A collection of already loaded objects.
    private static $collection = array();

    private function __construct() {

    }

    /* Returns the instance of a class registered on collection.
     * If the class isn't registered yet, create a new instance of that, register it on collection, then returns it.
     */

    public static function load($path, $classname, $args = array()) {
        if (!isset(self::$collection[$path])) {
            try {
                include_once $path;
                $r = new ReflectionClass(ucfirst($classname));
                self::$collection[$path] = $r->newInstanceArgs($args);
            } catch (Exception $ex) {
                throw $ex;
            }
        }

        return self::$collection[$path];
    }

}
