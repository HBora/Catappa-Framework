<?php

namespace Catappa\io;
/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */
/**
 * @name StoreMap
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package io
 * @version 1.0
 * @category io
 */

class StoreMap {
    const NUMBER_OF_BYTES_FOR_QUERY=512;
    const NUMBER_OF_BYTES_FOR_PARMETER=256;
    const NUMBER_OF_BYTES_FOR_KEY=256;
    const BUFFER_OF_BYTES=5120; //one record 1024 byte = 1kb buffer gets five records max 5 kb;
    const NUMBER_OF_BYTES_FOR_WRITE=1024;
    const NUMBER_OF_BYTES_FOR_PREMARY_KEY=4; //4 byte integer
    const RECORD_SEPARATE="|";
    const KEY_SEPARATE="\\";
    const PARAMETERS_SEPARATE=":";
    private $file;
    private $params;
    private $pk;

    function __construct() {
        $this->file = fopen(CLASS_PATH . \ApplicationContext::$ORM["ENTITY_PACKAGE"] . DS . "store.map", "a+b"); //Akım hem okumak hem de yazmak için açılır; dosya konumlayıcı dosyanın sonuna yerleştirilir. Dosya mevcut değilse oluşturulmaya çalışılır.
    }

    function getparamMap() {
        $arr = explode(":", $this->params);
        $prm = array();
        for ($i = 0, $k = count($arr); $i < $k; $i+=2) {
            $prm[$arr[$i]] = trim($arr[$i + 1]);
        }
        return $prm;
    }

    function isStored($find) {
        rewind($this->file);
        while (!feof($this->file)) {
            $buffer = fread($this->file, StoreMap::BUFFER_OF_BYTES);
            $vars = explode(StoreMap::RECORD_SEPARATE, $buffer);
            foreach ($vars as $value) {
                $key = explode(StoreMap::KEY_SEPARATE, $value);
                if ($find == trim($key[0])) {
                    $this->params = $key[2];
                    return rtrim($key[1]);
                }
            }
        }
        return false;
    }

    function getLastID($find) {
        rewind($this->file);
        while (!feof($this->file)) {
            $buffer = fread($this->file, StoreMap::BUFFER_OF_BYTES);
            $vars = explode(StoreMap::RECORD_SEPARATE, $buffer);
            foreach ($vars as $value) {
                $key = explode(StoreMap::KEY_SEPARATE, $value);
                if ($find == trim($key[0])) {
                    $this->params = $key[2];
                    return rtrim($key[1]);
                }
            }
        }
        return false;
    }

    function add($key, $chr, $params) {
        $key[StoreMap::NUMBER_OF_BYTES_FOR_KEY - 1] = StoreMap::KEY_SEPARATE;
        $chr[StoreMap::NUMBER_OF_BYTES_FOR_QUERY - 1] = StoreMap::KEY_SEPARATE;
        $params[StoreMap::NUMBER_OF_BYTES_FOR_PARMETER - 1] = StoreMap::RECORD_SEPARATE;
        fwrite($this->file, ((binary) $key . $chr . $params), StoreMap::NUMBER_OF_BYTES_FOR_WRITE);
    }

    public function __destruct() {
        fclose($this->file);
    }

}
?>