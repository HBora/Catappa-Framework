<?php

namespace Catappa\DataObject\Connectors;

/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */

/**
 * @name MysqlConnector
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package DataObject
 * @version 1.0
 * @category Catappa ORM
 */
use \PDO;
use \PDOException;
use Catappa\Collections\Config;

class MySQLConnector extends PDO {

    private $statement;
    private static $instance = null;

    public static function getInstance() {
        if (self::$instance != null)
            return self::$instance;
        self::$instance = new MySQLConnector();
        return self::$instance;
    }

    public function __construct($dns = "", $user = "", $pass = "") {
        self::$instance = $this;
        $confing = Config::getInstance();
        $this->connect();
        parent::quote("'");
    }

    public function nextResutlt() {
        return $this->statement->fetch(PDO::FETCH_ASSOC);
    }

    public function getinsertId() {

        return $this->lastInsertId();
    }

    public function getResults($sql) {
        $result = parent::query($sql);
        return $result->fetchAll(PDO::FETCH_OBJ);
    }

    /**
     *
     * @param string $query
     * @param ObjectSetter $setter
     * @return Query
     *
     */
    public function query($query, $setter = null, $type = "query") {
        $this->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $this->setAttribute(PDO::ATTR_FETCH_TABLE_NAMES, true);
        if ($setter == null)
            return parent::query($query);

        try {
            return parent::prepare($query, array(PDO::ATTR_FETCH_TABLE_NAMES => true, PDO::ATTR_STATEMENT_CLASS =>
                        array('Catappa\DataObject\Query\Query', array($setter, $type))));
        } catch (PDOException $e) {
            throw $e;
        }
    }

    /**
     *
     * @param string query
     * @return NativeQuery
     *
     */
    public function executeNativequery($query) {
        $this->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $this->setAttribute(PDO::ATTR_FETCH_TABLE_NAMES, false);
        return parent::prepare($query, array(PDO::ATTR_STATEMENT_CLASS =>
                    array('\Catappa\DataObject\Query\NativeQuery')));
    }

    public function connect() {
        try {
            $config = Config::getInstance();
            return parent::__construct(
                            $config->dns, $config->user, $config->password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $config->char_set, /* PDO::ATTR_PERSISTENT => TRUE, */ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false));
            $this->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        } catch (PDOException $exception) {
            throw $exception;
        }
    }

    function createProcedure($NAME, $PARAMS, $INNER_CODE) {
        $query .= "\nCREATE PROCEDURE `$NAME`($PARAMS)";
        $query .= "\nBEGIN";
        $query.="\n$INNER_CODE";
        $query .= "\nEND";
        return $query;
    }

}

