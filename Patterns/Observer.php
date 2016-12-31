<?php

/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */
/**
 * @name ObjectFactory
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package Patterns
 * @version 1.0
 * @category Patterns
 */

namespace ra\patterns;

interface Observer {

    function notify(Observable $obj, $args);
}

class Observable {

    private $observers = array();

    function notifyObservers($args) {

        foreach ($this->observers as $obj) {
            $obj->notify($this, $args);
        }
    }

    public function addObserver($obj) {
        $this->observers[] = $obj;
    }

}
?>
