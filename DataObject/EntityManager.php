<?php

namespace Catappa\DataObject;

/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */

/**
 * @name EntityManager
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package DataObject
 * @version 1.0
 * @category Catappa ORM
 */
use Catappa\Patterns\Singleton;
use Catappa\DataObject\Connectors\Connector;
use Catappa\Collections\Config;
use Catappa\DataObject\ClassMap;
use Catappa\DataObject\QueryGenerator;
use Catappa\DataObject\FqlToSql;
use Catappa\DataObject\ObjectSetter;
use Catappa\DataObject\Record;
use Catappa\DataObject\SQLGen;

class EntityManager extends Singleton {

    private $map;
    private $querymanager;
    private $queryParser;
    private $setter;
    private $objectRegistry;
    private $record;
    private $db;
    private $package, $path, $config;

    /**
     *
     * @return EntityManager
     *
     */
    public static function getInstance() {

        return parent::getInstance(__CLASS__);
    }

    function __construct() {
        $this->config = Config::getInstance();

        $this->map = ClassMap::getInstance();
        $this->objectRegistry = new \SplObjectStorage();
        $this->querymanager = new QueryGenerator($this->map);
        $this->package = $this->config->model_package;
        $this->db = Connector::getInstance();
        $this->queryParser = new FQLToSQL($this->querymanager, $this->map);
        $this->setter = new ObjectSetter($this->map, $this->objectRegistry, $this->db);
        $this->record = new Record($this->objectRegistry, $this->map, $this->db);
        $this->converter = null;
    }

    /**
     * @return \PDO
     */
    static function getPDOInstance() {
        $dbh = Connector::getInstance();
        $dbh->setAttribute(\PDO::ATTR_FETCH_TABLE_NAMES, false);
        return $dbh;
    }

    /**
     * 
     * @param string $entity
     * @param int $id
     * @param bool $join
     * @return \Catappa\DataObject\Model
     */
    function find($entity, $id, $join = true) {
        $this->map->merge($entity);
        $query = $this->querymanager->generate($join);
        $table_name = $this->map->getTableName($this->package . "\\" . $entity);
        $pkId = $this->map->getId($this->package . "\\" . $entity);
        $spq = $query . " WHERE " . $table_name . "." . "$pkId = userId;";
        $query.=" WHERE " . $table_name . "." . "$pkId = :$pkId";
        $statement = $this->db->query($query, $this->setter);
        $statement->bindParam(":$pkId", $id);
        $statement->execute();
        return $statement->getSingle();
    }

    /**
     * @param string $named
     * @return \Catappa\DataObject\Query\Query
     */
    function namedQuery($named) {
        $ex = \explode(".", $named);
        $query = $this->map->getQuery($ex[0], $ex[1]);
        return $this->db->query($query, $this->setter);
    }

    function merge($class_name) {
        $this->map->merge($class_name);
    }

    /**
     * @param string $entity
     * @return bool 
     */
    function save($entity) {
        $arr = explode("\\", get_class($entity));
        $n = \end($arr);
        $this->map->merge($n);
        return $this->record->save($entity);
    }

    /**
     * @param string $entity
     * @return bool 
     */
    function delete($entity, $subs = false) {
        $this->map->merge(get_class($entity));
        if ($subs == false)
            return $this->record->delete($entity);
        else
            return $this->record->deleterelation($entity);
    }

    /**
     * @param string $q
     * @param bool $isjoin
     * @return  \Catappa\DataObject\Query\Query
     */
    function createQuery($q, $isjoin = true) {
        $query = $this->queryParser->parseQuery($q, false, $isjoin);
   
        return $this->db->query($query, $this->setter, $this->queryParser->type);
    }

    /**
     * @param String $query
     * @return Catappa\DataObject\Query\Query
     */
    function nativeQuery($query) {

        return $this->db->executeNativequery($query);
    }

    function beginTransaction() {
        $this->db->beginTransaction();
    }

    function commit() {
        $this->db->commit();
    }

    function rollBack() {
        $this->db->rollBack();
    }

    /**
     * @return ClassMap
     */
    function &getClassMap() {
        return $this->map;
    }

    /**
     * @param string $entity
     * @return \Catappa\DataObject\Model
     */
    static function getSingle($query) {
        $db = Connector::getInstance();
        $query = $db->executeNativequery($query);
        $query->execute();
        return $query->fetchObject();
    }

    /**
     * 
     * @param string query
     * @return \Catappa\DataObject\Query\NativeQuery
     */
    static function query($query) {
        $db = Connector::getInstance();
        $query = $db->executeNativequery($query);
        return $query->execute();
    }

    /**
     * 
     * @param string $q
     * @return Catappa\Collections\ArrayList
     */
    static function getRResultList($q) {
        $query = EntityManager::getInstance()->createQuery($q);
        $query->execute();
        return $query->resultList();
    }

    static function quote($param) {
        return Connector::getInstance()->quote($param);
    }

    /**
     * @return \Catappa\DataObject\EQLGen* 
     */
    function getNewEQL() {
        return new \Catappa\DataObject\EQLGen($this);
    }

    /**
     * @return \Catappa\DataObject\SQLGen
     */
    function getNewSQL() {
        return new \Catappa\DataObject\SQLGen($this);
    }

}
