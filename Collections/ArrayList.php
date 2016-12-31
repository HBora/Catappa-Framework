<?php

namespace Catappa\Collections;

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