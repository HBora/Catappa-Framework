<?php

namespace Catappa\DataObject;

use Catappa\DataObject\EntityManager;
use Catappa\DataObject\Query\Query;
use \PDO;

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
class EQLGen {

    public $queryString = "";
    private $values = array();
    protected $em, $stmt = null;
    protected $cursor = 0;

    /**
     * @param \Catappa\DataObject\EntityManager $em
     * 
     */
    public function __construct(EntityManager $em) {
        $this->em = $em;
    }

    /**
     * @return type array
     */
    public function getValues() {
        return $this->values;
    }

    /**
     * @return \Catappa\DataObject\Query\Query;
     */
    public function createSTMT() {
        //
        //echo $this->queryString;
        if ($this->stmt == null)
            $this->stmt = $this->em->createQuery($this->queryString);
        foreach ($this->values as $obj) {
            $this->stmt->bindParam($obj->key, $obj->value, $obj->type);
        }
        return $this->stmt;
    }

    /**
     * @param String $is_join
     * @return \Catappa\Collections\ArrayList
     */
    public function getResultList($is_join = true) {
        $stmt = $this->createSTMT();
        $stmt->execute();
        $obj = $stmt->getResultList();
        $stmt->closeCursor();
        $this->clear();
        return $obj;
    }

    private function parse($q) {
        $regex = '('; # begin group
        $regex .= '(?:--|\\#)[\\ \\t\\S]*'; # inline comments
        $regex .= '|(?:<>|<=>|>=|<=|==|=|!=|!|<<|>>|<|>|\\|\\||\\||&&|&|-|\\+|\\*(?!\/)|\/(?!\\*)|\\%|~|\\^|\\?)'; # logical operators
        $regex .= '|[\\[\\]\\(\\),;`]|\\\'\\\'(?!\\\')|\\"\\"(?!\\"")'; # empty single/double quotes
        $regex .= '|".*?(?:(?:""){1,}"|(?<!["\\\\])"(?!")|\\\\"{2})|\'.*?(?:(?:\'\'){1,}\'|(?<![\'\\\\])\'(?!\')|\\\\\'{2})'; # quoted strings
        $regex .= '|\/\\*[\\ \\t\\n\\S]*?\\*\/'; # c style comments
        $regex .= '|(?:[\\w:@]+(?:\\.(?:\\w+|\\*)?)*)'; # words, placeholders, database.table.column strings
        $regex .= '|[\t\ ]+';
        $regex .= '|[\.]'; #period
        $regex .= ')'; # end group
        preg_match_all('/' . $regex . '/smx', $q, $arr);
        $arr = $arr[0];
        $count = count($arr);
        for ($i = 0; $i <= $count; $i++) {
            if (strlen(trim($arr[$i])) > 0) {
                $newarr[] = $arr[$i];
            }
        }
        return $newarr;
    }

    /**
     * @param String $param
     * @return \Catappa\DataObject\EQLGen
     */
    function select($param) {
        $this->queryString.="SELECT $param ";
        return $this;
    }

    /**
     * @param String $param
     * @return \Catappa\DataObject\EQLGen
     */
    function from($param) {
        $this->queryString.="FROM $param ";
        $this->entity = $param;
        return $this;
    }

    /**
     * @param String $param
     * @return \Catappa\DataObject\EQLGen
     */
    private function getType($var) {
        if (is_string($var))
            return PDO::PARAM_STR;
        else if (is_int($var))
            return PDO::PARAM_INT;
        else if (is_bool($var))
            return PDO::PARAM_BOOL;
        else
            return PDO::PARAM_STR;
    }

    /**
     * @param String $param
     * @return \Catappa\DataObject\EQLGen
     */
    function where($param) {
        $arr = $this->parse($param);
        $part = array();
        $cur = "";
        $count = count($arr);
        $changers = array();
        $oparant = array("=", "!=", "<>", "<", ">", "<=", ">=");
        for ($i = 0; $i <= $count; $i++) {
            $cur = $arr[$i];
            if (in_array($cur, $oparant)) {
                $this->cursor++;
                $changers[] = "?";
                $i++;
                $part[] = $arr[$i];
                $this->values[] = new Param($this->cursor, $arr[$i], $this->getType($arr[$i]));
            }
        }

        $changed = str_replace($part, $changers, $param);
        $this->queryString.="WHERE  $changed ";
        return $this;
    }

    /**
     * @param String $param
     * @return \Catappa\DataObject\EQLGen
     */
    function between($param, $param2) {
        $this->queryString.="BETWEEN ? AND ?";
        $this->cursor++;
        $this->values[] = new Param($this->cursor, $param, PDO::PARAM_INT);
        $this->cursor++;
        $this->values[] = new Param($this->cursor, $param2, PDO::PARAM_INT);
        return $this;
    }

    /**
     * @param String $param
     * @return \Catappa\DataObject\EQLGen
     */
    function in($param) {
        if (is_string($param))
            $fields = explode(",", $param);
        else
            $fields = $param;
        $q = "";
        foreach ($fields as $value) {
            $q.="?,";
            $this->cursor++;
            $this->values[] = new Param($this->cursor, $value, $this->getType($value));
        }
        $q = rtrim($q, ",");
        $this->queryString.="IN ($q) ";
        return $this;
    }

    /**
     * @param String $param
     * @return \Catappa\DataObject\EQLGen
     */
    function like($value) {
        $this->queryString.="LIKE ? ";
        $this->cursor++;
        $this->values[] = new Param($this->cursor, $value, $this->getType($value));
        return $this;
    }

    /**
     * @param String $param
     * @return \Catappa\DataObject\EQLGen
     */
    function limit($param, $param2) {
        $this->queryString.="LIMIT ?,?";
        $this->cursor++;
        $this->values[] = new Param($this->cursor, $param, PDO::PARAM_INT);
        $this->cursor++;
        $this->values[] = new Param($this->cursor, $param2, PDO::PARAM_INT);
        return $this;
    }

    /**
     * @param String $param
     * @return \Catappa\DataObject\EQLGen
     */
    function leftJoin($param, $parm2) {
        $this->queryString.="LEFT JOIN $param ON $parm2 ";
        return $this;
    }

    /**
     * @param String $param
     * @return \Catappa\DataObject\EQLGen
     */
    function innerJoin($param, $parm2) {
        $this->queryString.="INNER JOIN $param ON $parm2 ";
        return $this;
    }

    /**
     * @param String $param
     * @return \Catappa\DataObject\EQLGen
     */
    function join($param, $parm2) {
        $this->queryString.="JOIN $param ON $parm2 ";
        return $this;
    }

    /**
     * @param String $param
     * @return \Catappa\DataObject\EQLGen
     */
    function orderBY($param) {
        $this->queryString.="ORDER BY $param ";
        return $this;
    }

    /**
     * @param String $param
     * @return \Catappa\DataObject\EQLGen
     */
    function desc() {
        $this->queryString.="DESC ";
        return $this;
    }

    /**
     * @param String $param
     * @return \Catappa\DataObject\EQLGen
     */
    function asc($param) {
        $this->queryString.="asc $param ";
        return $this;
    }

    /**
     * @param String $param
     * @return \Catappa\DataObject\EQLGen
     */
    function having($param) {
        $arr = $this->parse($param);
        $part = array();
        $cur = "";
        $count = count($arr);
        $changers = array();
        $oparant = array("=", "!=", "<>", "<", ">", "<=", ">=");
        for ($i = 0; $i <= $count; $i++) {
            $cur = $arr[$i];
            if (in_array($cur, $oparant)) {
                $this->cursor++;
                $changers[] = "?";
                $i++;
                $part[] = $arr[$i];
                $this->values[] = new Param($this->cursor, $arr[$i], $this->getType($arr[$i]));
            }
        }

        $changed = str_replace($part, $changers, $param);
        $this->queryString.="HAVING $changed ";
        return $this;
    }

    /**
     * @param String $param
     * @return \Catappa\DataObject\EQLGen
     */
    function or_($param = "") {
        $this->queryString.="OR $param ";
        return $this;
    }

    /**
     * @param String $param
     * @return \Catappa\DataObject\EQLGen
     */
    function and_($param = "") {
        $this->queryString.="AND $param ";
        return $this;
    }

    /**
     * @param String $param
     * @return \Catappa\DataObject\EQLGen
     */
    public function clear() {
        $this->queryString = "";
        $this->from = null;
        $this->entity = NULL;
        return $this;
    }

}
