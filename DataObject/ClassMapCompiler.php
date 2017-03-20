<?php

namespace Catappa\DataObject;

/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */

/**
 * @name ClassMapCompiler
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package DatObject
 * @version 3.0
 * @category Catappa  ORM
 */
use Catappa\Patterns\Singleton;
use Serializable;
use Catappa\DataObject\Connectors\Connector;
use Catappa\Collections\Config;
use Catappa\DataObject;
use Catappa\Exceptions\CompilerException;
use Catappa\DataObject\FqlToSql;

class ClassMapCompiler extends Singleton implements Serializable {

    private $array_map = array();
    private $class_list = array();
    private $super_class;
    private $package, $path = "";
    private $generator;
    private $queryParser;
    private $line;
    private $config;
    private static $VALIDATORS = ["", "Required", "Blank", "NotBlank", "Email", "Choice", "NotNull", "IsNull",
        "Length", "Url", "Ip", "Regex", "UserPassword", "EqualTo", "Choice", "Date", "DataAndTime", "Time", "Iban",
        "CardScheme", "Uuid", "IsTrue", "IsFalse"];

    /**
     *
     * @return <ClassMap>
     *
     */
    public static function getInstance() {

        return parent::getInstance(__CLASS__);
    }

    public function serialize() {
        return serialize($this->array_map);
    }

    public function unserialize($serialized) {
        $this->array_map = unserialize($serialized);
    }

    function compile($self = false) {
        if ($this->config->model_compile_message)
            echo "<p><b> Class Map Compiler</b></p>";
        if ($this->config->model_compile == TRUE || $self == TRUE) {

            require_once 'annotations.php';
            require_once 'annotation.php';
            $iter = new \DirectoryIterator($this->path);
            $file = new \SplFileInfo($file_name);

            $array = array();

            foreach ($iter as $file) {
                if (end(explode(".", $file->getFilename())) == "php") {
                    require_once $file->getRealPath();
                    $clazz = substr($file->getFilename(), 0, strpos($file->getFilename(), "."));
                    $clazz = substr(\strtoupper($clazz), 0, 1) . substr($clazz, 1);
                    $this->run($clazz);
                    if ($this->config->model_compile_message)
                        echo "<p> Compiled  Class : $clazz </p>";
                }
            }
            foreach ($this->class_list as $clazz) {
                $this->namedQueries($clazz);
            }
            $this->saveTo();
        }
    }

    function __construct() {
        $this->config = Config::getInstance();
        $this->path = $this->config->model_path;
        $this->package = $this->config->model_package;
        $this->generator = new QueryGenerator($this);
        $this->queryParser = new FqlToSql($this->generator, $this);
    }

    function run($class_name) {
        $class_name = $this->package . "\\" . $class_name;
        if ($this->super_class != $class_name) {
            $this->super_class = $class_name;
            if ($class_name != null)
                $this->doClassMap($class_name);
        }
    }

    private function classSet($class_name) {

        $this->array_map[$class_name] = array();
        $this->array_map[$class_name]["many"] = array();
        $this->array_map[$class_name]["one"] = array();
    }

    private function namedQueries($class_name) {
        $reflection = new \ReflectionAnnotatedClass($class_name);
        $array = $reflection->getAllAnnotations("NamedQuery");
        $this->array_map[$class_name]["QUERIES"] = array();

        foreach ($array as $value) {
            $fql = $value->query;
            $query = $this->queryParser->parseQuery($fql);
            $this->array_map[$class_name]["QUERIES"][$value->name] = $query;
        }
        $array = $reflection->getAllAnnotations("StoredQuery");
        $db = Connector::getInstance();
        foreach ($array as $value) {
            $fql = $value->query;
            $query = $this->queryParser->parseQuery($fql, true) . ";";
            $prms = $this->queryParser->getSpParams();
            $sp = $db->createProcedure($value->name, $prms, $query);
            $call = "CALL $value->name(" . $this->queryParser->getSpPHPParams() . ")";
            $this->array_map[$class_name]["QUERIES"][$value->name] = $call;
            $db->exec("DROP PROCEDURE IF EXISTS `$value->name`");
            $db->exec($sp);
        }
    }

    private function saveTo() {
        $path = $this->config->model_path . DS . $this->config->model_map_dir_name;
        foreach ($this->array_map as $key => $value) {
         
            $this->ownerMap[$key] = $value;
            foreach ($value[many] as $many) {
                $this->ownerMap[$many["class"]] = $this->array_map [$many["class"]];
            }
            foreach ($value[one] as $one) {
                $this->ownerMap[$one["class"]] = $this->array_map [$one["class"]];
            }

            $name = strtolower(basename(str_replace('\\', '/',$key)));

            $fp = fopen($path . DS . "$name.map", 'w');
            $this->ownerMap["QUERIES"] = array();
            fwrite($fp, \serialize($this->ownerMap));
            fclose($fp);
            unset($this->ownerMap);
        }
   
    }

    private function doClassMap($class_name) {
        $this->line = 0;
        try {
            if (isset($this->class_list[$class_name]))
                return;
            $this->class_list[$class_name] = $class_name;
            $this->classSet($class_name);
            $reflection = new \ReflectionAnnotatedClass($class_name);

            $this->array_map[$class_name]["table"] = $reflection->getAnnotation('Table')->name;

            foreach ($reflection->getProperties(\ReflectionProperty::IS_PRIVATE) as $m) {

                if ($m->hasAnnotation("Column")) {
                    $column_name = $m->getAnnotation("Column")->name;
                    $column_type = $m->getAnnotation("Column")->type;
                    $column_size = $m->getAnnotation("Column")->size;
                    $property_name = $m->getname();
                    $this->array_map[$class_name]["coulmns"][$column_name] = array("name" => $column_name, "type" => $column_type, "property" => $property_name, "size" => $column_size);
                }

                if ($m->hasAnnotation("OneToOne"))
                    $this->hasOne("OneToOne", $m, $class_name);

                if ($m->hasAnnotation("ManyToOne"))
                    $this->hasOne("ManyToOne", $m, $class_name);

                if ($m->hasAnnotation("OneToMany"))
                    $this->hasMany("OneToMany", $m, $class_name);

                if ($m->hasAnnotation("ManyToMany"))
                    $this->hasMany("ManyToMany", $m, $class_name);

                if ($m->hasAnnotation("Id")) {
                    $column_name = $m->getAnnotation("Column")->name;
                    $column_type = $m->getAnnotation("Column")->type;
                    $this->array_map[$class_name]["id"]["id"] = $column_name;
                    $this->array_map[$class_name]["id"]["type"] = $column_type;
                    $this->array_map[$class_name]["id"]["property"] = $m->getName();
                    if ($m->hasAnnotation("Generated"))
                        $this->array_map[$class_name]["id"]["generated"] = "generated";
                }

                /* Validation Annoted */
                $anots = $m->getAllAnnotations();
                foreach ($anots as $a) {
                    $anot_class = get_class($a);
                    if (array_search($anot_class, ClassMapCompiler::$VALIDATORS))
                        $this->addValidation($class_name, $m, $anot_class);
                }
            }
            $reflection = null;
        } catch (\Exception $e) {

            throw new CompilerException($e->getMessage(), 5000, $reflection->getFileName(), $reflection->get);
        }
    }

    function addValidation($class_name, $propery, $annoted_name) {
        $parameters = get_object_vars($propery->getAnnotation($annoted_name));
        unset($parameters["value"]);
        $property_name = $propery->getname();
        if (!is_array($this->array_map[$class_name]["validations"][$property_name]))
            $this->array_map[$class_name]["validations"][$property_name] = array();
        if ($annoted_name == "Required")
            $annoted_name = "NotBlank";
        $this->array_map[$class_name]["validations"][$property_name] = array_merge($this->array_map[$class_name]["validations"][$property_name], array($annoted_name => $parameters));
    }

    function hasOne($notation, $m, $class_name) {

        $cocuk_class = $this->package . "\\" . $m->getAnnotation($notation)->mappedBy;

        $fk = $m->getAnnotation($notation)->fk;
        $pk = $m->getAnnotation($notation)->pk;
        $property_name = $m->getname();
//   $this->array_map[$class_name]["one"][]= array("class" => $cocuk_class, "property" => $property_name, "fk" => $fk, "pk" => $pk);
        if ($this->super_class == $cocuk_class) {
            $this->array_map[$class_name]["one"][] = array("class" => $cocuk_class, "property" => $property_name, "fk" => $fk, "pk" => $pk, "line" => $this->line);
            $this->line++;
        } else
            $this->array_map[$class_name]["one"][] = array("class" => $cocuk_class, "property" => $property_name, "fk" => $fk, "pk" => $pk, "line" => "-1");
        /*  if(!isset($this->class_list[$cocuk_class]))
          $this->HaritaYap($cocuk_class); */
    }

    function hasMany($notation, $m, $class_name) {

        $cocuk_class = $this->package . "\\" . $m->getAnnotation($notation)->mappedBy;
        $fk = $m->getAnnotation($notation)->fk;
        $pk = $m->getAnnotation($notation)->pk;
        $property_name = $m->getname();

        if ($this->super_class == $cocuk_class) {
            $this->array_map[$class_name]["many"][] = array("class" => $cocuk_class, "property" => $property_name, "fk" => $fk, "pk" => $pk, "line" => $this->line);
            $this->line++;
        } else
            array_push($this->array_map[$class_name]["many"], array("class" => $cocuk_class, "property" => $property_name, "fk" => $fk, "pk" => $pk, "line" => "-1"));
        /* if(!isset($this->class_list[$cocuk_class]))
          $this->HaritaYap($cocuk_class); */
    }

    function merge($class_name) {
        $this->simpleClassName = $class_name;
        $class_name = $this->package . "\\" . $class_name;
        if ($this->super_class != $class_name) {
            $this->super_class = $class_name;
            if (!$this->isLoaded($class_name))
                $this->load();
        }
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

    function getClassColumns($class_name) {
        return $this->array_map[$class_name]["coulmns"];
    }

    function getClassColumn($class_name, $column_name) {
        return $this->array_map[$class_name]["coulmns"][$column_name];
    }

    function getTableName($class_name) {
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

}
