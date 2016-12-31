<?php

namespace Catappa\DataObject;

/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */
/**
 * @name FqlToSql
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package DataObject
 * @version 1.0
 * @category Catappa ORM
 */
use Catappa\Collections\Config;

class FqlToSql {

    public $parser;
    public $map;
    public $qm;
    public $q;
    public $anasinif;
    public $kelime;
    public $eskikelime;
    public $type = "query";
    public $paketvesinif;
    private $sp = false;
    private $sp_params;
    private $sp_php_params;
    private $cur_clmn;
    private $config;

    function __construct(QueryGenerator &$qm = null, &$map) {
        $this->map = &$map; //ClassMap::getInstance();
        $this->config = $this->qm = $qm; //new QueryGenerator($this->map);
        $this->config = Config::getInstance();
    }

    function getSpParams() {
        return rtrim($this->sp_params, ",");
    }

    function getSpPHPParams() {
        return rtrim($this->sp_php_params, ",");
    }

    function clear() {
        $this->sp_params = "";
        $this->sp_php_params = "";
        $this->q = "";
        $this->eskikelime = "";
    }

    function parseQuery($sql, $sp = false, $isjoin = true) {
        $this->sp = $sp;
        $this->parser = new SqlParser();
        $arr = $this->parser->parssing($sql);
        $this->clear();

        $this->type = $this->parser->type;
        $this->qm->clear();
        $clmns = array();
        $alias = "";
        $q = "";
        $query = array();
        $sel = false;
        $fro = false;
        $k = 0;
        foreach ($arr as $data) {

            $this->kelime = trim(strtolower($data["soz"]));
            if ($this->kelime == "from" && $fro == false) {
                $table = $this->map->getTableName($this->paketvesinif);
                $query["FROM"] = $table;
                $fro = true;
            } else if ($this->kelime == "select" && $sel == false) {
                $from = $this->parser->nextFrom();
                $this->anasinif = ucfirst(strtolower($from["class"]));
                $this->paketvesinif = $this->config->model_package . "\\" . $this->anasinif;
                $alias = $from["alias"];

                //  $this->qm->setAlias($alias);
                // $this->qm->setSelects(implode($data["val"]));

                $this->map->merge($this->anasinif);

                $clmns = $this->map->getClassColumns($this->paketvesinif);

                $this->yelestir($query, $data["val"], $clmns, $alias, $k);

                if ($this->type == "query")
                    $this->qm->generate();
                else {
                    //$this->yelestir($query, $data["val"], $clmns, $alias, $k);
                    continue;
                }
                $clmns = $this->map->getClassColumns($this->paketvesinif);
                $query["SELECT"] = "";
                $sel = true;
            } else if ($this->kelime == "subquery") {
                $this->sub($this->parser->sub[$data["val"][0]], $k, $query);
                unset($data["val"][0]);
                $this->yelestir($query, $data["val"], $clmns, $alias, $k);
            } else
                $this->yelestir($query, $data["val"], $clmns, $alias, $k);
            $this->eskikelime = $this->kelime;
        }
        /* SQL oluştur */
        foreach ($query as $key => $vlas) {

            if ($key == "SELECT" && $sel == true) {

                //$q.= " " . $this->qm->select . " ";
                $join = true;
                $sel = false;
            } elseif ($key == "FROM" && $fro == true) {
                if ($join && $isjoin)
                    $q.= $key . " $vlas " . "" . $this->qm->birlestir . " ";
                else
                    $q.= $key . " " . $vlas . " ";
                $fro = false;
            }
            else {
                $soz = explode("|", $key);
                $q.= strtoupper($soz[0]) . " " . $vlas . " ";
            }
        }
        return $q;
    }

    function yelestir(&$query, $arr, $clmns, $alias, &$k) {
        if ($this->eskikelime != $this->kelime)
            $k++;
        $cur_clmn = "";
        $queryval = $query[$this->kelime . "|" . $k];
        $query[$this->kelime . "|" . $k] = " ";

        $datas = &$arr;

        $param = 0;
        foreach ($datas as $key => $value) {

            if (strpos($value, ".")) {
                $ex = explode(".", $value);

                $count = count($ex);
                for ($i = 0; $i < $count; $i++) {
                    $val = $ex[$i];

                    if ($count < 3) {
                        $this->cur_clmn = $clmns[$val];
                        if ($val == $alias)
                            $queryval.= "" . $this->map->getTableName($this->paketvesinif);
                        if ($val == $clmns[$val]["property"])
                            $queryval.="." . $clmns[$val]["name"] . " ";
                        elseif ($val == "*")
                            $queryval = "* ";
                    }
                    else {
                        if ($i > 0) {
                            $foreigns = $this->map->getForeignClassesMap($this->paketvesinif);
                            $this->classInside($foreigns, $ex, $queryval);
                            break;
                        }
                    }
                }
            } elseif ($this->sp && strpos($datas[$key + 1], ":") > -1) {
                $queryval.= " " . $value . " ";

                if (strpos($datas[$key + 1], ":") > -1) {
                    $this->sp_php_params.=$datas[$key + 1];
                    $datas[$key + 1] = "sp_" . ltrim($datas[$key + 1], ":");
                }
                $prm_name = $datas[$key + 1];
                $this->sp_params.="IN $prm_name " . $this->cur_clmn["type"];

                if ($this->cur_clmn["size"])
                    $this->sp_params.="(" . $this->cur_clmn["size"] . "),";
            }
            elseif ($value == "(")
                $queryval = trim($queryval) . $value;
            else
            if ($this->kelime == "select")
                $queryval.= $value;
            else
                $queryval.= " " . $value . " ";
        }
        $query[$this->kelime . "|" . $k] = " " . $queryval;
    }

    function sub(&$arr, &$k, &$query) {
        $map = $this->map;
        $anasinif = ucfirst(strtolower($arr["from"]));
        $paketvesinif = $this->config->model_package . "\\" . $anasinif;
        $tbl_name.= " " . $map->getTableName($paketvesinif);
        $alias = "i";
        if (!$map->isClassLoaded($paketvesinif)) {
            $map = new ClassMap($anasinif);
        }

        $clmns = $map->getClassColumns($paketvesinif);
        foreach ($arr as $key => $data) {

            $kelime = strtolower($data["soz"]);
            if ($kelime == "from")
                unset($data["val"][1]);
            $k++;
            $queryval = "";
            if (is_array($data["val"])) {
                $this->kelime = $kelime;

                $this->eskikelime = $this->kelime;

                foreach ($data["val"] as $value)
                    if (strpos($value, ".")) {
                        $ex = explode(".", $value);
                        $count = count($ex);

                        for ($i = 0; $i < $count; $i++) {
                            $val = $ex[$i];
                            if ($count < 3) {
                                if ($val == $alias)
                                    $queryval.= " " . $tbl_name;
                                if ($val == $clmns[$val]["property"])
                                    $queryval.="." . $clmns[$val]["name"];
                            }
                            else {
                                if ($i > 0) {
                                    $foreigns = $map->getForeignClassesMap($paketvesinif);
                                    $this->classInside($foreigns, $ex, $queryval);

                                    break;
                                }
                            }
                        }
                    } else
                        $queryval.= "" . $value . "";
                $query[$kelime . "|" . $k] = "" . $queryval;
            }
        }
        $k--;
    }

    function classInside($foreigns, &$ex, &$str, $map = null) {
        $ex = array_slice($ex, 1);
        $break = false;
        $val = $ex[0];
        if ($map == null)
            $map = $this->map;

        foreach ($foreigns as $key => $has) {
            foreach ($has as $u) {
                $prop = $u["property"];
                $fkclass = $u["class"];
                if ($prop == $ex[0]) {
                    $fkclmn = $map->getClassColumn($fkclass, $u["fk"]);
                    if ($fkclmn)
                        if (count($ex) > 2)
                            $break = true;
                    break;
                }
            }
            if ($break)
                break;
            if (count($ex) > 2) {
                $foreigns = $map->getForeignClassesMap($fkclass);
                $this->classInside($foreigns, $ex, $str);
                return;
            }
            if (count($ex) > 0) {
                $clms = $map->getClassColumns($fkclass);
                $table = $map->getTableName($fkclass);
                $val = $ex[1];
                if ($val == $clms[$val]["property"] && $ex[0] == $prop) {
                    $str.= " " . $table;
                    $str.= "." . $clms[$val]["name"];
                }
            }
        }
    }

}
