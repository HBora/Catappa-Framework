<?php

/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */

/**
 * @name PropertyChangeSupport
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package Collection
 * @version 1.0
 *
 */

namespace Catappa\Collections;

use Catappa\Patterns\Singleton;

class Config extends Singleton {

    private $map = array();

    /**
     * @return <Catappa\Collections\Config>
     */
    public static function getInstance() {
        return parent::getInstance(__CLASS__);
    }

    public function __set($name, $value) {
        $this->map[$name] = $value;
    }

    public function __get($name) {
        return $this->map[$name];
    }

    public function setAttributes(array $cfg) {
        $this->map = $cfg;
    }

    function getAttributes() {
        return $this->cfg;
    }

}

?>
