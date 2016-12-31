<?php
namespace Catappa\DataObject;
class Param {

    public $key, $value, $type;

    public function __construct($key, $value, $type) {
        $this->key = $key;
        $this->value = $value;
        $this->type = $type;
    }

}
