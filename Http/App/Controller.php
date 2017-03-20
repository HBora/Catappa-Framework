<?php

namespace Catappa\Http\App;

/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */

/**
 * @name Controler
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package Http
 * @version 1.0
 * @category Catappa HTTP/AppController
 */
use \Catappa;
use \PDO;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Catappa\Http\Controller AS IController;
use Catappa\DataObject\EntityManager;

class Controller implements IController {

    protected $response;
    protected $request;
    protected $dbh;

    public function __construct() {
        $this->request = Catappa::getInstance()->getHttpRequest();
        $this->response = Catappa::getInstance()->getHttpResponse();
        $this->dbh = EntityManager::getInstance()->getPDOInstance();
    }

    public function query($sql, $params = array(), $all = true) {
        $stmt = $this->dbh->prepare($sql);
        $stmt->execute($params);
        if ($all)
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        return $stmt->fetchObject();
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getHttpRequest() {
        return $this->request;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getHttpResponse() {
        return $this->response;
    }

    /**
     * @return \PDO
     */
    public function getPDO() {
        return $this->dbh;
    }

}
