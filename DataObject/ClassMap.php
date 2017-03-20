<?php

namespace Catappa\DataObject;

/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */

/**
 * @name ClassMap
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package DataObject
 * @version 2.0
 * @category Catappa  ORM
 */
use Catappa\Patterns\Singleton;
use Catappa\Collections\Config;
use Serializable;

class ClassMap extends Singleton implements Serializable {

    /**
     * @return ClassMap
     */
    public static function getInstance() {

        return parent::getInstance(__CLASS__);
    }

    private $array_map = array(), $loaded_files = array();
    private $super_class;
    private $package, $path, $map_dir;
    private $simpleClassName;
    private $config;

    public function __construct() {
        $this->config = Config::getInstance();
        $this->package = $this->config->model_package; //\Route::$app_package . "\\Models";
        $this->path = $this->config->model_path;
        $this->map_dir = $this->config->model_map_dir_name;

        if ($this->config->model_compile) {
            $mapper = new ClassMapCompiler($this);
            $mapper->compile();
        }
    }

    function Compile() {
        $mapper = new ClassMapCompiler($this);
        $mapper->compile(true);
    }

    function load($clazz) {
        $file_name = strtolower(basename(str_replace('\\', '/', $clazz)));
        $full_name = $this->path . DS . $this->map_dir . DS . $file_name . ".map";

        if (!array_key_exists($clazz, $this->loaded_files))
            if (file_exists($full_name)) {
                $x = unserialize(file_get_contents($full_name));
                $this->loaded_files[$clazz] = $file_name;
                $this->array_map = array_merge($this->array_map, $x);
            } else
                echo "<p>Map notfound $file_name </p>";
    }

    function setArrayMap(Array &$map) {
        $this->array_map = &$map;
    }

    function merge($class_name, $o = false) {
        if ($o == true)
            $class_name = end(explode("\\", $class_name));
        $this->simpleClassName = $class_name;
        $class_name = $this->package . "\\" . $class_name;

        if ($this->super_class != $class_name) {
            $this->super_class = $class_name;
            if (!$this->isLoaded($class_name))
                $this->load($this->simpleClassName);
        }
        return $class_name;
    }

    function getSimpleName() {
        return $this->simpleClassName;
    }

    function isLoaded($class_name) {
        return isset($this->array_map[$class_name]);
    }

    function clearMap() {
        unset($this->array_map);
        $this->array_map = array();
    }

    function getSuperClass() {
        return $this->super_class;
    }

    function getArrayMap() {
        return $this->array_map;
    }

    function getClass($class_name) {
        return $this->array_map[$class_name];
    }

    function getClassColumns($class_name, $merge = false) {
        if ($merge == true)
            $class_name = $this->merge($class_name, $merge);
        return $this->array_map[$class_name]["coulmns"];
    }

    function getClassColumn($class_name, $column_name) {
        return $this->array_map[$class_name]["coulmns"][$column_name];
    }

    function getTableName($class_name) {

        if (isset($this->array_map[$class_name]))
            return $this->array_map[$class_name]["table"];

        if (strlen(trim($class_name)) > 1) {

            $this->load(end(explode("\\", $class_name)));
        }
        return $this->array_map[$class_name]["table"];
    }

    function getClassOne($class_name) {
        return $this->array_map[$class_name]["one"];
    }

    function getClassMany($class_name) {
        return $this->array_map[$class_name]["many"];
    }

    function getForeignClassesMap($class_name) {
        return array("one" => $this->array_map[$class_name]["one"], "many" => $this->array_map[$class_name]["many"]);
    }

    function getArrayId($class_name) {
        return $this->array_map[$class_name]["id"];
    }

    function getId($class_name) {
        return $this->array_map[$class_name]["id"]["id"];
    }

    function getQuery($class_name, $name) {
        $this->merge($class_name);
        return $this->array_map[$this->super_class]["QUERIES"][$name];
    }

    function getSP($class_name, $name) {
        $this->merge($class_name);
        return $this->array_map[$this->super_class]["SP"][$name];
    }

    function getClassValidations($class_name, $merge = false) {
        if ($merge == true)
            $class_name = $this->merge($class_name, $merge);
        return $this->array_map[$class_name]["validations"];
    }

    public function serialize() {
        return serialize($this->array_map);
    }

    public function unserialize($serialized) {
        $this->array_map = unserialize($serialized);
    }

    function save() {
        $compile_path = $this->path . DS . "compiled";
        $fp = fopen($compile_path . DS . "compiled.map", 'w');
        fwrite($fp, $this->serialize());
        fclose($fp);
    }

}
