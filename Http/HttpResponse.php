<?php

namespace Catappa\Http;
/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */
/**
 * @name HttpResponse
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package Http
 * @version 1.0
 * @category Catappa HTTP
 */
use Zend\Diactoros\Response;

// $psrRequest is an instance of Psr\Http\Message\ServerRequestInterface


class HttpResponse extends Response  {

    public function __construct($body = 'php://memory', $status = 200, array $headers = array()) {
        parent::__construct($body, $status, $headers);
    }

}

?>