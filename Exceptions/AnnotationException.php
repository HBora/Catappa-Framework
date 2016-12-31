<?php

namespace Catappa\Exceptions;

use \Exception;

class AnnotationException extends Exception {

    public function __construct($message, $code, $file) {

        parent::__construct($message, $code, null);
    
    }

}
