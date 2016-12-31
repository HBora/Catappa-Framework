<?php

namespace Catappa\DataObject\Query;

use \PDOStatement;
use \PDO;
use Catappa\DataObject\ObjectSetter;
use Catappa\DataObject\ClassMap;

class Query extends PDOStatement {

    private $setter;
    private $results = array();
    private $type;

    protected function __construct(ObjectSetter $object_setter = null, $type = null) {
        $this->setter = $object_setter;
        $this->type = $type;
    }

    /**
     * @return \Catappa\Collections\ArrayList
     */
    public function getResultList() {
        return $this->results; //$this->setter->kulucka($this);
    }

    public function nextResutlt() {
        return $this->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllResutlt() {
        return $this->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
    }

    public function getSingle() {
        //$res= $this->setter->kulucka($this);
        if (count($this->results) > 0)
            return current($this->results);
        return null;
    }

    /**
     * @return \Catappa\DataObject\Query\Query
     * @throws \Catappa\DataObject\Query\PDOException
     */
    public function execute($values = null, $is_orm = true) {
        try {
            if ($values == null)
                parent::execute();
            else
                parent::execute($values);
        } catch (PDOException $e) {
            throw $e;
        }
        if ($is_orm == false)
            return $this;
        if ($this->type == "query")
            $this->results = $this->setter->kulucka($this);
        else
            array_push($this->results, $this->fetchColumn());
        return $this;
    }

}

