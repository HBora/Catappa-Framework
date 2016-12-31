<?php

namespace Catappa\Http;
/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */
/**
 * @name HTTP Request
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package Http
 * @version 1.0
 * @category Catappa HTTP
 */
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Zend\Diactoros\ServerRequest;

// $psrRequest is an instance of Psr\Http\Message\ServerRequestInterface


class HttpRequest extends ServerRequest {

    public function __construct(array $serverParams = array(), array $uploadedFiles = array(), $uri = null, $method = null, $body = 'php://input', array $headers = array(), array $cookies = array(), array $queryParams = array(), $parsedBody = null, $protocol = '1.1') {
        parent::__construct($serverParams, $uploadedFiles, $uri, $method, $body, $headers, $cookies, $queryParams, $parsedBody, $protocol);
    }

}

?>