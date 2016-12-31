<?php

/**
 * @author H.B.ABACI
 * @package Soa
 * @since 2010
 */

namespace Catappa\Http\Soap;

use Catappa\Http\Soap\Parameters;

class Client extends \SoapClient {

    private $types = array();

    public function __construct($wsdl, $options = array()) {

        if (!isset($options["classmap"])) {
            $this->types = $this->classmap($wsdl, $options["namespace"]);
            $options["classmap"] = $this->types;
        }
        parent::SoapClient($wsdl, $options);
    }

    private function classmap($wsdlUri, $nameSpace = '') {
        $soap = new \SoapClient($wsdlUri);
        $types = array();

        foreach ($soap->__getTypes() as $type) {

            preg_match("/([a-z0-9_]+)\s+([a-z0-9_]+(\[\])?)(.*)?/si", $type, $matches);

            $type = $matches[1];
            $name = $matches[2];
            if ($type == "struct") {
                $className = $nameSpace . '\\' . $name;
                if (class_exists($className)) {
                    $types[$name] = $className;
                }
            }
        }
        return $types;
    }

    function __call($function_name, $arguments) {

        if ($arguments[0] instanceof Parameters)
            $response = $this->__soapCall($function_name, $arguments[0]->getParameters());
        else
            $response = $this->__soapCall($function_name, $arguments);


        foreach ($this->types as $key => $type) {
            if (property_exists($response, $key))
                return $response->{$key};
        }

        if (property_exists($response, "Response"))
            return $response->Response;

        if (property_exists($response, "item")) {
            $item = $response->item;
            $ref = new \ReflectionObject($item->value);
            $prty = $ref->getProperties();
            return $prty[0]->getValue($item->value);
        }
        return $response;
    }

}
