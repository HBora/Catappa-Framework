<?php
namespace Catappa\DataObject;

/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */
/**
 * @name QueryManager
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package DataObject
 * @version 1.0
 * @category Catappa ORM
 */

use Catappa\Patterns\Singleton;
use Catappa\DataObject\Connectors\Connector;

class QueryManager extends Singleton {

    private $query;
    private $entity = null;
    private $from = null;

    /**
     *
     * @return QueryManager
     * 
     */
    public static function getInstance() {
        return parent::getInstance(__CLASS__);
    }

    function __construct() {

        $this->db = Connector::getInstance();
    }

    /**
     * @param Class $table_name
     * @param Int $id
     * @return $table_name
     *
     */
    function find($table_name, $id) {
        $clazz = MVC_PACKAGE . "\\Models\\" . $table_name;
        $tbl = strtolower($table_name);
        $query = "SELECT * FROM $tbl ";
        $pkId = $clazz::getPrimaryKey();
        $query.=" WHERE " . $tbl . "." . "$pkId = $id";

        $statement = $this->db->executeNativequery($query);
        return $statement->getSingle($table_name);
    }

    /**
     *  @return NativeQuery
     */
    function query($param, $clear = false) {
        $this->query = $param;
        $return = $this->db->executeNativequery($this->query);
        if ($clear)
            $this->query = "";
        return $return;
    }

    /**
     *  @return NativeQuery
     */
    function getStatement() {
        return $this->db->executeNativequery($this->query);
    }

    function select($param) {
        $this->query.="SELECT $param ";
        return $this;
    }

    function from($param) {
        $this->query.="FROM $param ";
        return $this;
    }

    function fromModel($param) {

        $clazz = MVC_PACKAGE . "\\models\\" . $param;
        $table = strtolower($param);
        $this->query.="FROM $table ";
        $this->from = $clazz;
        return $this;
    }

    function fromEntity($param) {
        $this->query.="FROM $param ";
        $this->entity = $param;
        return $this;
    }

    function where($param) {
        $this->query.="WHERE $param ";
        return $this;
    }

    function between($param, $param2) {
        $this->query.="BETWEEN $param AND $param2";
        return $this;
    }

    function in($param) {
        $fields = implode(",", $param);
        $this->query.="IN ($fields) ";
        return $this;
    }

    function like($param) {
        $this->query.="LIKE $param ";
        return $this;
    }

    function limit($param, $param2) {
        $this->query.="LIMIT $param,$param2 ";
        return $this;
    }

    function leftJoin($param, $parm2) {
        $this->query.="LEFT JOIN $param ON $parm2 ";
        return $this;
    }

    function innerJoin($param, $parm2) {
        $this->query.="INNER JOIN $param ON $parm2 ";
        return $this;
    }

    function join($param, $parm2) {
        $this->query.="JOIN $param ON $parm2 ";
        return $this;
    }

    function orderBY($param) {
        $this->query.="ORDER BY $param ";
        return $this;
    }

    function desc() {
        $this->query.="DESC ";
        return $this;
    }

    function asc($param) {
        $this->query.="asc $param ";
        return $this;
    }

    function update($table, $param) {
        $this->query.="UPDATE $table SET $param ";
        return $this;
    }

    function delete($param) {
        $this->query.="DELETE $param ";
        return $this;
    }

    function insert($table, $param, $param2) {
        $fields = implode(",", $param);
        $values = implode(",", $param2);
        $this->query.="INSERT INTO $table($fields) VALUES($values)";
        return $this;
    }

    function having($param) {
        $this->query.="HAVING $param ";
        return $this;
    }

    function or_($param = "") {
        $this->query.="OR $param ";
        return $this;
    }

    function and_($param = "") {
        $this->query.="AND $param ";
        return $this;
    }

    public function getQuery() {
        return $this->query;
    }

    public function clear() {
        $this->query = "";
        $this->from = null;
        $this->entity = NULL;
        return $this;
    }

    /**
     *
     * @return ArrayObject
     *
     */
    public function execute() {
        if ($this->entity == null)
            $statement = $this->db->executeNativequery($this->query);
        else
            $statement = EntityManager::getInstance()->createQuery($this->query);

        $statement->execute();

        $obj = new \ArrayObject($statement->getResultList($this->from));
        $statement->closeCursor();
        //$statement = null;
        //$this->clear();
        return $obj;
    }

    /**
     *  @return NativeQuery
     */
    public function __call($name, $arguments) {
        $this->db->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        $this->db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, true);
        $str = "CALL $name";
        foreach ($arguments as $arg)
            $params.="?,";
        $params = \rtrim($params, ",");
        $call = $str . "($params)";

        $stmt = $this->db->prepare($call);

        $stmt->execute($arguments);
        $result = $stmt->fetchAll();
        $stmt->closeCursor();

        $stmt = null;
        return $result;
    }

    /**
     *  @return NativeQuery
     */
    public function call($name, $arguments) {

        $this->__call($name, $arguments);
    }

}
