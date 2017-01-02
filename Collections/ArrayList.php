<?php

namespace Catappa\Collections;

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
use \ArrayObject;

class ArrayList extends ArrayObject {

    public function toJson() {
        return json_encode($this->getArrayCopy(), JSON_PRETTY_PRINT);
    }

    public function printJson() {
        echo json_encode($this->getArrayCopy(), JSON_PRETTY_PRINT);
    }

    public function add($value) {
        return parent::append($value);
    }

}
