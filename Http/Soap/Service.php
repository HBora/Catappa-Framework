<?php

/**
 * @author H.B.ABACI
 * @package Soap
 * @since 2010
 */

namespace Catappa\Http\Soap;

use \Exception;
use \SoapFault;
use \SoapServer;

class Service {

    private $object;
    private $wsgen = null;
    private $wsdl;
    protected $soapServer;

    public function __construct($obj, $wsdl = null) {
        $this->object = $obj;
        if ($wsdl == null) {
            $pos = \strripos($_SERVER["REQUEST_URI"], "?");
            $wsdl = 'http://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . "/?wsdl";
            if (!array_key_exists("wsdl", $_GET))
                $wsdl = 'http://' . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"] . "?wsdl";
        }
        $this->wsdl = $wsdl;
    }

    public function handle() {
        $this->showWSDL(false);
        $this->soapServer = new SoapServer($this->wsdl, array('trace' => true, 'classmap' => $this->wsgen->class_map));
        $this->soapServer->setObject($this);
        $this->soapServer->handle();
    }

    public function __call($name, $arguments) {
     
        try {
            if ($arguments[0] instanceof \stdClass) {
                foreach ($arguments[0] as $args)
                    $arr[] = $args;

                $response = call_user_func_array(array($this->object, $name), $arr);
            } else
                $response = call_user_func_array(array($this->object, $name), $arguments);
           
            if ($response instanceof SoapFault)
                throw $response;
            if (is_object($response)) {

                $class_name = substr(\get_class($response), strrpos(\get_class($response), "\\") + 1);
                return array("$class_name" => $response);
            }

            return array("Response" => $response);
        } catch (Exception $e) {

            throw new SoapFault((string) $e->getCode(), $e->getMessage());
        }
    }

    function showWSDL($show = true) {
        if ($this->wsgen == null)
            $this->wsgen = new WSGen();

        $this->wsgen->gen(get_class($this->object), $show);
    }

}
