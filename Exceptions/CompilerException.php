<?php

namespace Catappa\Exceptions;

use \Exception;

class CompilerException extends Exception {

    public function __construct($message, $code, $file,$line=0) {
 
        parent::__construct($message, $code, null);
        $this->file = $file;
        $this->line =$line;
    }

}
