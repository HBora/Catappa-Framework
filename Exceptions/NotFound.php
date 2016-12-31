<?php

namespace Catappa\Exceptions;

use \Exception;

use Route;

class NotFound extends Exception {

    public function __construct($message, $code, $file) {
  
        parent::__construct($message, $code, null);
    }

}
