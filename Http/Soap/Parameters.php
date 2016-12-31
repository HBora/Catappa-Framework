<?php
/**
 * @author H.B.ABACI
 * @package Soa
 * @since 2010
 */
namespace Catappa\Http\Soap;

class Parameters {

    private $parameters;

    function __construct($param=array()) {
        $this->parameters = array();
    }

    function addParam($name, $value) {
        $this->parameters[$name] = $value;
    }

    function addObject($name, &$value) {
        $this->parameters[$name] = &$value;
    }

    function getParameters() {
        return array($this->parameters);
    }

}