<?php
namespace Catappa\Http\Soap;
class SoapParam {

    private $name, $value;

    function __construct ($value,$name) {
        $this->name = $name;
        $this->value = $value;
    }

}

