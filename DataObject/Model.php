<?php

namespace Catappa\DataObject;

/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */

/**
 * @name Model
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package DataObject
 * @version 3.0
 * @category Catappa ORM
 */
use Catappa\Collections\PropertyChangeSupport;
use Catappa\DataObject\ClassMap;
use Catappa\DataObject\Query\Query;
use Catappa\DataObject\Query\NativeQuery;
use Catappa\DataObject\Connectors\Connector;
use Catappa\DataObject\Validator;
use Catappa\Collections\Config;
use Catappa\Patterns\ObjectFactory;

abstract class Model implements \JsonSerializable {

    protected $changeSupport = PropertyChangeSupport;
    private $_columns;
    private $map;
    private $validator = null, $violation;

    public function __construct($arr = null) {
        $this->changeSupport = new PropertyChangeSupport($this);
        $this->map = ClassMap::getInstance();
        $this->_columns = $this->map->getClassColumns(\get_class($this), true);
        if (is_array($arr))
            $this->setAll($arr);
    }

    private function support() {
        $this->changeSupport = new PropertyChangeSupport($this);
    }

    public final function __set($name, $value) {
        $oldValue = $this->__get($name);
        if ($this->changeSupport)
            $this->support();
        $this->changeSupport->firePropertyChange($name, $oldValue, $value);
        if (method_exists($this, "set$name"))
            call_user_func(array($this, "set$name"), $value);
        if (method_exists($this, "set"))
            call_user_func_array(array($this, "set"), array($name, $value));
    }

    public final function __get($name) {
        if (method_exists($this, "get$name"))
            return call_user_func(array($this, "get$name"));
        elseif (method_exists($this, "get"))
            return call_user_func(array($this, "get"), $name);
    }

    public function __dbset($name, $value) {

        if (method_exists($this, "set"))
            call_user_func(array($this, "set"), $name, $value);
    }

    /**
     * @return \Catappa\DataObject\Model
     */
    public function save($values = null) {
        if ($values != null)
            $this->setAll($values);
        if (Config::getInstance()->model_auto_mode == TRUE) {
            if ($this->isValid())
                EntityManager::getInstance()->save($this);
            return $this;
        }
        return EntityManager::getInstance()->save($this);
    }

    /**
     * @return bool
     */
    public function delete() {
        return EntityManager::getInstance()->delete($this);
    }

    public function valueChanged($name, $value) {
        $oldValue = $this->__get($name);
        if ($this->changeSupport)
            $this->support();
        $this->changeSupport->firePropertyChange($name, $oldValue, $value);
    }

    /**
     * 
     * @param Integer $pk
     * @return \Catappa\DataObject\Model
     */
    public static function find($pk, $join = true) {
        $class = basename(str_replace('\\', '/', get_called_class()));
        return EntityManager::getInstance()->find($class, $pk, $join);
    }

    /**
     * 
     * @param Integer $pk
     * @return \Catappa\DataObject\Model
     */
    public static function findBy($column, $value, $join = true) {
        $entity_class = basename(str_replace('\\', '/', get_called_class()));

        $query = EntityManager::getInstance()->createQuery("SELECT * FROM $entity_class e WHERE e.$column =:value", $join);
        $query->bindValue(":value", $value);
        $query->execute();
        $arr = $query->getResultList();
        if ($arr instanceof \Catappa\Collections\ArrayList)
            if ($arr->count() > 1)
                return $arr;
        return $arr[0];
    }

    /**
     * @param String $eql
     * @return \Catappa\DataObject\EQLGen
     */
    public static function where($where) {
        $entity_class = basename(str_replace('\\', '/', get_called_class()));
        $eql = EntityManager::getInstance()->getNewEQL();
        
        return $eql->select("*")->from($entity_class)->where($where);
    }

    /**
     * @return \Catappa\DataObject\Model
     */
    public static function create($arr = NULL) {
        return ObjectFactory::getNewInstance(get_called_class(), $arr);
    }

    /**
     * 
     * @param int $pk
     * @return boolean
     */
    public static function remove($pk) {
        $object = EntityManager::getInstance()->find(str_replace('\\', '/', get_called_class()), $pk);
        if ($object) {
            $object->delete();
            return true;
        }
        return false;
    }

    /**
     * @param string $query
     * @param boolean  $isjoin
     * @return \Catappa\Collections\ArrayList
     */
    public static function getResultList($query, $isjoin = true) {
        $query = EntityManager::getInstance()->createQuery($query, $isjoin);
        $query->execute();
        $query->closeCursor();
        return $query->getResultList();
    }

    /**
     * @param string $param
     * @param bool $isjoin
     * @return \Catappa\DataObject\Model
     */
    public static function getSingle($param, $isjoin = true) {
        $query = EntityManager::getInstance()->createQuery($param, $isjoin);
        $query->execute();
        $query->closeCursor();
        return $query->getSingle();
    }

    /**
     * @param string $param
     * @return int
     */
    public static function getCount($query = "") {
        $clazz = get_called_class();
        EntityManager::getInstance()->merge($clazz);
        $map = EntityManager::getInstance()->getClassMap();
        $table_name = $map->getTableName($clazz);
        $pk = $map->getId($clazz);
        $q = "SELECT COUNT($pk) FROM $table_name";

        $query = EntityManager::getInstance()->nativeQuery($q);
        $query->execute();
        return $query->getSingle();
    }

    /**
     * @param array $values
     * @return \Catappa\DataObject\Model
     */
    public function setAll(array $values) {
        foreach ($values as $key => $val) {
            $this->__set($key, $val);
        }
        return $this;
    }

    /**
     * 
     * @param Bool $q
     * @param String $isjoin
     * @return \Catappa\DataObject\Query\Query;
     */
    public static function query($q, $isjoin = true) {
        $query = EntityManager::getInstance()->createQuery($q, $isjoin);
        return $query;
    }

    /**
     * 
     * @param String $queyName
     * @return \Catappa\DataObject\Query\Query;
     */
    public static function callQuery($queyName) {
        $clazz_name = basename(str_replace('\\', '/', get_called_class()));
        $query = EntityManager::getInstance()->namedQuery($clazz_name . "." . $queyName, $params);
        return $query;
    }

    public static function escape($param) {
        return Connector::getInstance()->quote($param);
    }

    /**
     * @return Boolean
     */
    public function isValid() {
        $map = EntityManager::getInstance()->getClassMap()->getClassValidations(get_class($this));
        $this->validator = new Validator();
        foreach ($map as $prop => $validations) {
            $result = $this->validator->validationArray($prop, $this->get($prop), $validations);
            if (count($result) !== 0)
                $this->violation[$prop] = $result;
        }
        return (bool) (count($this->violation) == 0);
    }

    /**
     * @return array
     */
    public function getViolation() {
        return $this->violation;
    }

    public function getMessages() {
        return $this->validator->getMessages();
    }

    public function printMessagesJSON() {
        echo json_encode($this->validator->getMessages(), JSON_PRETTY_PRINT);
    }

    abstract function set($name, $value);

    abstract function &get($name);

    public function toJSON() {
        return json_encode($this, JSON_PRETTY_PRINT);
    }

    public function printJSON() {

        echo json_encode($this, JSON_PRETTY_PRINT);
    }

    public function jsonSerialize() {
        $map = EntityManager::getInstance()->getClassMap()->getClassColumns(get_class($this));
        $arr = array();
        foreach ($map as $prp) {
            $property_name = $prp["property"];
            $arr[$property_name] = $this->get($property_name);
        }
        return $arr;
    }

    public function toArray() {
        return $this->jsonSerialize();
    }

}
