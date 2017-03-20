<?php

namespace Catappa\DataObject;

/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */

/**
 * @name QueryGenerator
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package DataObject
 * @version 2.0
 * @category Catappa ORM
 */
use Catappa\Collections\PropertyChangeSupport;
use Catappa\Patterns\Singleton;

class QueryGenerator extends Singleton {

    private $class_map;
    private $islenmis_haritalar = array();
    var $birlestir;
    var $select = "*";
    var $alias;
    private $super_class;

    //private $s = 0;

    static function getInstance() {
        return parent::getInstance(__CLASS__);
    }

    function __construct(&$map) {

        $this->class_map = &$map; //&ClassMap::getInstance();
    }

    function getQuery() {

        $table = $this->class_map->getTableName($this->super_class);
        $query = "SELECT $this->select FROM  $table $this->alias $this->birlestir";
        return $query;
    }

    function clear() {
        $this->islenmis_haritalar = array();
        $this->super_class = $this->class_map->getSuperClass();
        $this->birlestir = "";
    }

    function setSelects($s) {

        $this->select = trim($s);
        // echo "s = $s<br>";
    }

    function setAlias($s) {
        $this->alias = $s;
    }

    function generate($join = true) {
        $old = $this->super_class;
        $this->super_class = $this->class_map->getSuperClass();
        if ($old != $this->super_class)
            $this->clear();
        if ($join)
            $this->doJoins($this->super_class);
        return $this->getQuery();
    }

    function doJoins($sinif_adi) {

        if (isset($this->islenmis_haritalar[$sinif_adi]))
            return;
        $this->islenmis_haritalar[$sinif_adi] = $sinif_adi;
        $sinif_haritasi = $this->class_map->getForeignClassesMap($sinif_adi);
        $table_name = $this->class_map->getTableName($sinif_adi);


        foreach ($sinif_haritasi as $rel => $deger) {

            if ($deger)
                foreach ($deger as $oge) {

                    $islenecek_sinif_adi = $oge["class"];
                    if (!isset($this->islenmis_haritalar[$islenecek_sinif_adi]) || $oge["line"] != -1) {
                        $itable_name = $this->class_map->getTableName($islenecek_sinif_adi);

                        if ($itable_name) {
                            $harici_anahtar = $oge["fk"];
                            $dahili_anahtar = $oge["pk"];
                            if ($oge["line"] != -1) {
                                $line = $oge["line"];
                                $join = " LEFT JOIN $itable_name as self_$line ON self_$line.$dahili_anahtar = $table_name.$harici_anahtar";
                            } else {
                                $join = " LEFT JOIN $itable_name ON $itable_name.$harici_anahtar = $table_name.$dahili_anahtar";
                            }
                            $this->birlestir.=$join;
                            $this->doJoins($islenecek_sinif_adi);
                        }
                    }
                }
        }
    }

    function sp($NAME, $PARAMS, $INNER_CODE) {
        // $query="DROP PROCEDURE IF EXISTS `$NAME`";
        //$query .= "\nDROP PROCEDURE `$NAME`";
        $query .= "\nCREATE PROCEDURE `$NAME`($PARAMS)";
        $query .= "\nBEGIN";
        $query.="\n$INNER_CODE";
        $query .= "\nEND";
        return $query;
    }

}

class Record {

    var $registry;
    var $map;
    var $lasted;
    var $updates;
    var $inserts;
    var $query;
    var $db;
    var $changeSupport;

    function __construct(\SplObjectStorage $registry, ClassMap $map, $db) {

        $this->registry = $registry;
        $this->map = $map;

        $this->db = $db;
        $this->lasted = array();
        $this->changeSupport = PropertyChangeSupport::getInstance();
    }

    public function save($entity) {

        $hash = spl_object_hash($entity);
        /* if (isset($this->lasted[$hash]))
          return; */

        $this->lasted[$hash] = $hash;
        $class_name = get_class($entity);
        $pkId = $this->map->getId($class_name);
        $fk = $this->map->getForeignClassesMap($class_name);
        if ($this->registry->contains($entity)) {
            if ($this->changeSupport->isChangedObject($entity))
                $this->update($entity);
        } else
        //if (isset($this->lasted[$hash]))
            $this->insert($entity);

        foreach ($fk as $key => $maps)
            foreach ($maps as $map) {

                $property = $map["property"];
                $foreign = $map["fk"];
                $pk = $map["pk"];
                $child_class = $map["class"];
                if (is_object($entity->$property)) {
                    $foreign_map = $this->map->getClassColumn($child_class, $foreign);
                    $foreign_property = $foreign_map["property"];

                    if ($key == "many")
                        foreach ($entity->$property as $obj) {
                            if ($obj->$foreign_property == NULL)
                                $obj->__dbset($foreign_property, $entity);
                            $this->save($obj);
                        }else {
                        //  if($entity->$property->$foreign_property==null)
                        $this->save($entity->$property);
                    }
                }
            }
    }

    public function insert($entity) {
        $class_name = get_class($entity);
        $table_name = $this->map->getTableName($class_name);
        $columns = $this->map->getClassColumns($class_name);
        $prkey = $this->map->getId($class_name);
        $str = "INSERT INTO " . $table_name . " (";
        $values = "VALUES(";
        $arrvalues = array();
        $i = 0;

        foreach ($columns as $key => $val) {
            $property = $val["property"];
            $column_name = $val["name"];
            $column_type = $val["type"];
            $data = $entity->$property; {
                if (!is_null($data) && !is_array($data) && !is_object($data)) {
                    $str.= ( $i == 0) ? $column_name : "," . $column_name;
                    $values.= ( $i == 0) ? "?" : ",?";
                    array_push($arrvalues, $data);
                    $i++;
                } elseif (is_object($data)) {
                    $pkId = $this->map->getId(get_class($data));
                    if (!is_object($data->$pkId)) {
                        $str.= ( $i == 0) ? $column_name : "," . $column_name;
                        $values.= ( $i == 0) ? "?" : ",?";
                        $i++;
                        $val = $data->$pkId;
                        array_push($arrvalues, $val);
                    }
                }
            }
            $insert = $str . ")" . $values . ");";
        }
        if ($i > 0) {
            try {
              
                $this->db->executeNativequery($insert)->execute($arrvalues);
                $entity->__dbset($prkey, $this->db->getinsertId());
                $this->registry->attach($entity);
            } catch (PDOException $e) {
                throw $e;
            }
        }
    }

    function update($entity) {

        $class_name = get_class($entity);
        $table_name = $this->map->getTableName($class_name);
        $columns = $this->map->getClassColumns($class_name);
        $pkId = $this->map->getId($class_name);
        $str = "UPDATE " . $table_name . " SET ";

        $where = "";
        $arrvalues = array();
        $i = 0;

        foreach ($columns as $key => $val) {
            $property = $val["property"];
            $column_name = $val["name"];
            if (!$this->changeSupport->isPropertyChange($property, $entity))
                continue;
            $data = $entity->$property;

            if (!is_null($data) && !is_array($data) && !is_object($data)) {
                $str.= ( $i == 0) ? $column_name . "=? ," : $column_name . "=? ,";
                array_push($arrvalues, $data);
                $i++;
            } elseif (is_object($data)) {
                $pkId = $this->map->getId(get_class($data));
                if (!is_object($data->$pkId)) {
                    $str.= ( $i == 0) ? $column_name . "=? ," : $column_name . "=? ,";
                    $i++;
                    $val = $data->$pkId;
                    array_push($arrvalues, $val);
                }
            }
        }
        if ($i > 0) {
            $where = " WHERE $table_name.$pkId = " . $entity->$pkId;
            $update = substr($str, 0, strlen($str) - 1) . $values . $where . "; ";
           
            try {
                $this->db->executeNativequery($update)->execute($arrvalues);
            } catch (PDOException $e) {
                throw $e;
            }
        }
    }

    function delete($entity) {
        $class_name = get_class($entity);
        $table_name = $this->map->getTableName($class_name);
        $pkId = $this->map->getId($class_name);
        $sql = "DELETE  FROM $table_name WHERE $pkId =:id";
        try {

            $statement = $this->db->executeNativequery($sql);
            $statement->bindValue("id", $entity->$pkId);
            $statement->execute();
            $entity = null;
        } catch (PDOException $e) {
            throw $e;
        }
    }

}
