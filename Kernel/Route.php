<?php

/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */

/**
 * @name Route
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package Kernel
 * @version 2
 * @category Catappa Kernel
 */
use Catappa\Patterns\ObjectFactory;
use Catappa\Collections\Config;
use Catappa\Http\MiddleWare;
use Catappa\Http\DiactorosFactory;
use Catappa\Http\HttpRequest;
use Catappa\Http\HttpResponse;

class Route {

    private static $nothing = array();
    private static $badHTTPMethod = array();
    private static $uri = array();
    private static $app_packages = array();
    private static $appFrontController = null;
    public static $app_package = "";
    public static $app_path = "";
    public static $app_alias = "", $ctrl_alias = "", $type = "";
    public static $midleMap = array();
    private static $routes = array();

    static function addController($uris, $class_name, $midle_ware_class = null) {
        if (is_array($uris)) {
            foreach ($uris as $uri) {
                $uri = strtolower($uri);
                Route::$routes[$uri] = array("alias" => $uri,
                    "type" => "controller",
                    "class" => Route::$app_package . "\\Controllers\\" . $class_name,
                );
                Route::$routes[$uri]["class"] = ($class_name instanceof Closure) ? Route::$app_package . "\\Controllers\\" . $class_name : $class_name;
                Route::addMiddleWare($uris, $midle_ware_class);
                if (!$class_name instanceof Closure)
                    Route::$uri[Route::$app_package . "\\Controllers\\" . $class_name] = $uri;
            }
        } else {
            $uris = strtolower($uris);
            Route::$routes[$uris] = array("alias" => $uris,
                "type" => "controller",
            );
            Route::$routes[$uris]["class"] = ($class_name instanceof Closure) ? $class_name : Route::$app_package . "\\Controllers\\" . $class_name;
            Route::addMiddleWare($uris, $midle_ware_class);
            if (!$class_name instanceof Closure)
                Route::$uri[Route::$app_package . "\\Controllers\\" . $class_name] = $uris;
        }
    }

    static function addMiddleWare($uris, $midles = null) {
        if ($midles == null)
            return;
        if (is_array($uris)) {
            foreach ($uris as $uri) {
                if (is_array($midles))
                    if (is_array(Route::$midleMap[strtolower($uri)]))
                        Route::$midleMap[strtolower($uri)] = array_merge(Route::$midleMap[strtolower($uri)], $midles);
                    else
                        Route::$midleMap[strtolower($uri)] = $midles;
                else
                    Route::$midleMap[strtolower($uri)][] = $midles;
            }
            return;
        }
        if (is_array($midles))
            if (is_array(Route::$midleMap[strtolower($uris)]))
                Route::$midleMap[strtolower($uris)] = array_merge(Route::$midleMap[strtolower($uris)], $midles);
            else
                Route::$midleMap[strtolower($uris)] = $midles;
        else
            Route::$midleMap[strtolower($uris)][] = $midles;
    }

    static function set($name, $class_name, $midle = array()) {
        Route::addController($name, $class_name, $midle);
    }

    static function add($name, $class_name, $midle = array()) {
        Route::addController($name, $class_name, $midle);
    }

    static function addApp($name, $function) {

        Route::$app_packages[strtolower($name)]["app"] = $function;
    }

    static function setAppFrontController($midleWare) {
        Route::$appFrontController = $midleWare;
    }

    static function getAppFrontController() {
        return Route::$appFrontController;
    }

    static function setNothing($nothing) {
        Route::$nothing = $nothing;
    }

    static function getNothing() {
        if (is_callable(Route::$nothing))
            return call_user_func_array(Route::$nothing, $_GET["NEW_CATAPPA_PARAMS"]);
        if (class_exists(Route::$nothing))
            return ObjectFactory::getNewInstance(Route::$nothing);
    }

    static function getClassUri($class_name) {

        if (isset(Route::$uri[$class_name]))
            return Route::$uri[$class_name];
        return false;
    }

    static function setIncorrectHTTPMethod($param) {
        Route::$badHTTPMethod = $param;
    }

    static function callIncorrectHTTPMethod() {

        if (is_callable(Route::$badHTTPMethod))
            return call_user_func_array(Route::$badHTTPMethod, $_GET["NEW_CATAPPA_PARAMS"]);

        else if (class_exists(Route::$badHTTPMethod))
            return ObjectFactory::getNewInstance(Route::$badHTTPMethod);
    }

    static function clear() {
        Route::$routes = array();
        Route::$uri = array();
        Route::$midleMap = array();
        Route::$appFrontController = null;
    }

    private static $reCall = false;
    private static $currentParam = null;

    static function hasRoutable($param) {
        $param = strtolower($param);
        if ($param == "")
            $param = "/";

        if (array_key_exists($param, Route::$routes)) {
            $route = Route::$routes[$param];


            Route::$currentParam = $param;
            if ($route["class"] instanceof Closure)
                return call_user_func_array($route["class"], array()); //Request Response PSR7
            elseif ($route["type"] == "controller") {
                //pre( BASE_DIR .DS.str_replace("\\",DS , $route["class"].".php"));
                include BASE_DIR . DS . str_replace("\\", DS, $route["class"] . ".php");
                Route::$ctrl_alias = $route["alias"];
                return ObjectFactory::getNewInstance($route["class"]);
            }
        }
        return false;
    }

    static function runMiddleWares($midles = null) {
        $psr7Factory = DiactorosFactory::getInstance();
        $request = Catappa::getInstance()->getHttpRequest();
        $response = Catappa::getInstance()->getHttpResponse();

        $midle_result = true;
        if ($midles == null)
            $midles = Route::$midleMap[Route::$currentParam];


        foreach ($midles as $midle) {

            $send_method_params = array();
            $midle_object = ObjectFactory::getNewInstance($midle);
            if ($midle_object instanceof MiddleWare) {
                $ref_method = new ReflectionMethod(get_class($midle_object), "next");
                $ref_params = $ref_method->getParameters();
                if (count($ref_params) > 0) {
                    foreach ($ref_params as $par) {
                        if ($par->getClass() !== NULL) {
                            $class = $par->getClass();
                            if ($class->name == "Symfony\Component\HttpFoundation\Response")
                                $send_method_params[$par->getPosition()] = $response;
                            else if ($class->name == "Symfony\Component\HttpFoundation\Request")
                                $send_method_params[$par->getPosition()] = $request;
                            else if ($class->name == "Catappa\Http\HttpRequest")
                                $send_method_params[$par->getPosition()] = $psr7Factory->createRequest($request);
                            else if ($class->name == "Catappa\Http\HttpResponse")
                                $send_method_params[$par->getPosition()] = $psr7Factory->createResponse($response);
                        }
                    }
                }

                $midle_result = call_user_func_array(array($midle_object, "next"), $send_method_params);
            }//Request Response PSR7
            if ($midle_result == false)
                return false;
            else if ($midle_result instanceof \Catappa\Http\MiddleWare)
                return Route::runMiddleWares($midle_result);
            elseif (is_object($midle_result))
                return $midle_result;
        }

        return $midle_result ? $midle_result : true;
    }

    static function mergeMidleMap($arr = array()) {
        Route::$midleMap[Route::$currentParam] = array_merge(Route::$midleMap[Route::$currentParam], $arr);
    }

    static function isApp($param) {
        $key = strtolower($param);
        if (!array_key_exists($key, Route::$app_packages))
            $key = "/";
        if (array_key_exists($key, Route::$app_packages)) {
            if (is_callable(Route::$app_packages[$key]["app"])) {
                Route::$app_package = "Apps\\" . call_user_func_array(Route::$app_packages[$key]["app"], array());
                Route::$app_path = BASE_DIR . DS . str_replace("\\", DS, Route::$app_package);
                Route::$app_alias = ucfirst($key);
                if (file_exists(Route::$app_path . DS . "Route.php"))
                    include Route::$app_path . DS . "Route.php";
                if (file_exists(Route::$app_path . DS . "Config.php"))
                    include Route::$app_path . DS . "Config.php";
                return ($key != "/") ? Catappa::getInstance()->dispatch() : $param;
            }
        }
    }

}