<?php

/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */
/**
 * @name Singleton
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package Patterns
 * @version 3
 * @category Patterns
 */

namespace Catappa\Patterns;

class Singleton {

    private static $_instances = array();

    /**
     * @param string $classname
     * @return Singleton
     */
    public static function getInstance() {
        $classname = func_get_arg(0);

        if (!isset(self::$_instances[$classname])) {
            self::$_instances[$classname] = new $classname();
        }
        return Singleton::$_instances[$classname];
    }

}

?>
