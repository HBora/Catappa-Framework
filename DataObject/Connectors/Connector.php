<?php

namespace Catappa\DataObject\Connectors;

/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */

/**
 * @name Connector
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package DataObject
 * @version 1.0
 * @category Catappa ORM
 */
use Catappa\Patterns\ObjectFactory;
use Catappa\Collections\Config;

class Connector {

    private static $instance = null;
    private static $connectors = array(
        "MYSQL" => "Catappa\DataObject\Connectors\MySQLConnector",
        "ORACLE" => "Catappa\DataObject\OracleConnector",
        "MSSQL" => "Catappa\DataObject\MsSQLConnector",
    );

    public function __construct() {
        
    }

    public static function getInstance() {
        if (self::$instance != null)
            return self::$instance;
        $connector = Config::getInstance()->connector;
        if (array_key_exists($connector, self::$connectors))
            self::$instance = ObjectFactory::getNewInstance(self::$connectors[$connector]);
        return self::$instance;
    }

    function createProcedure($NAME, $PARAMS, $INNER_CODE) {
        if (method_exists(Connector::$instance, "createProcedure"))
            return Connector::$instance->createProcedure($NAME, $PARAMS, $INNER_CODE);
        throw new \Exception("<br/>Catappa Error :Connector Not Suported Procedures");
    }

}
