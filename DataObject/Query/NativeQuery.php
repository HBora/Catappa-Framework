<?php

namespace Catappa\DataObject\Query;

use \PDOStatement;
use \PDO;
use \ArrayObject;

class NativeQuery extends PDOStatement {

    protected function __construct() {
        
    }

    public function nextResutlt() {
        return $this->fetch(PDO::FETCH_ASSOC);
    }

    public function nextObject($className = null) {
        if ($className == null)
            return $this->fetch(PDO::FETCH_OBJ);
        return $this->fetchObject($className);
    }

    public function getSingle($class = null) {
        $this->execute();
        if ($class == null) {
            $res = $this->fetch();
            return $res[0];
        }
        $clazz = $class;
        return $this->fetchObject($clazz, array("Record"));
    }

    public function execute($input_parameters = array()) {
        if (\count($input_parameters) > 0)
            parent::execute($input_parameters);
        else
            parent::execute();

        return $this;
    }

    public function getResultList($class = null) {
        if ($class == null) {
            return new ArrayObject($this->fetchAll(PDO::FETCH_OBJ));
        }
        return new ArrayObject($this->fetchAll(PDO::FETCH_CLASS, $class, array("Record")));
    }

}

?>