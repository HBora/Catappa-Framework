<?php

/**
 * @author H.B.ABACI
 * @package Soa
 * @since 2010
 */

namespace Catappa\Http\Soap;

use Catappa\Http\Soap\Service;

class Soap {

    public $service;

    public function __construct() {

        $this->service = new Service($this);

        if (array_key_exists("wsdl",$_GET)) {
            $this->service->showWSDL();
        } else {
           
            $this->service->handle();
        }
    }

}

