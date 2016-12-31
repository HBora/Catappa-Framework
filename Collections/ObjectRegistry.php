<?php
/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */
/**
 * @name ObjectRegistry
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package Collection
 * @version 1.0
 * @category Catappa Collection
 */

namespace Catappa\Collections;

class ObjectRegistry extends Singleton {

    var $registed = array();

    public static function getInstance() {
        return parent::getInstance(__CLASS__);
    }

    public function ObjectRegistry() {
        $this->registed = array();
    }

    public function add($obj) {
        $hash = spl_object_hash($obj);
        $this->registed[$hash] = get_class($obj);
    }

    public function isRegisted($obj) {
        $hash = spl_object_hash($obj);
        return (isset($this->registed[$hash]));
    }

    public function remove($obj) {
        $hash = spl_object_hash($obj);
        unset($this->registed[$hash]);
    }

}

?>
