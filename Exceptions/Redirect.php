<?php

namespace Catappa\Exceptions;

use \Exception;


class Redirect extends Exception {

    public function __construct($message, $code, $file) {
  
        parent::__construct($message, $code, null);
    }

}
