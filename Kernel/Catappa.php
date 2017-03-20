<?php

/*
 * The Catappa Kernel
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */

/**
 * @name Catappa
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package Kernel
 * @version 3
 * @category Catappa Kernel
 */
use Catappa\Http\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Catappa\Http\HttpRequest;
use Catappa\Http\HttpResponse;
use Catappa\Patterns\ObjectFactory;
use Catappa\Patterns\Singleton;
use Composer\Autoload\ClassLoader;
use Catappa\Kernel\ExceptionHandler;

error_reporting(E_ALL & ~(E_STRICT | E_NOTICE | E_WARNING));
ini_set("display_errors", 1);
require_once "Route.php";
if (file_exists(BASE_DIR . DS . "Apps" . DS . "AppContext.php"))
    require_once BASE_DIR . DS . "Apps" . DS . "AppContext.php";
if (file_exists(BASE_DIR . DS . "Apps" . DS . "AppConfig.php"))
    require_once BASE_DIR . DS . "Apps" . DS . "AppConfig.php";


$cfg = Catappa\Collections\Config::getInstance();
if ($cfg->error_handling == TRUE) {
    set_exception_handler(array("Catappa\Kernel\ExceptionHandler", "handleException"));
    register_shutdown_function(array("Catappa\Kernel\ExceptionHandler", "handleShutdown"));
}


/* * Modify $_GET */
$par = explode("?", urldecode($_SERVER["REQUEST_URI"]));
$par = explode("&", $par[1]);
$count = count($par);
for ($i = 0; $i < $count; $i++) {
    $xparam = explode("=", $par[$i]);
    if (stripos($xparam[0], "[]")) {
        $name = str_replace("[]", "", $xparam[0]);
        $_GET[$name][] = $xparam[1];
    } else
        $_GET[$xparam[0]] = $xparam[1];
}

function pre($param) {
    echo "<pre>";
    print_r($param);
    echo "</pre>";
}

class Catappa extends Singleton {

    private $isMethodCall = false, $isIncorrectHTTP = false;
    private $dispatchId = -1;
    private $symfonyRequest, $symfonyResponse;
    private static $AVAILABLE_HTTP_MEHHODS = array("@ALL" => "ALL",
        "@GET" => "GET", "@POST" => "POST", "@PATCH" => "PATCH",
        "@PUT" => "PUT", "@DELETE" => "DELETE");

    /**
     * @return Catappa <Catappa>
     */
    public static function getInstance() {
        return parent::getInstance(__CLASS__);
    }

    function dispatch() {

        $this->dispatchId++;
        global $URI_GET;
        $params = explode("/", $_GET["CATAPPA_URI_PARAMS"]);
        $URI_GET = $params;
        $_GET["NEW_CATAPPA_PARAMS"] = $params;
        unset($_GET["NEW_CATAPPA_PARAMS"][$this->dispatchId]);
        if (strlen(trim($URI_GET[$this->dispatchId])) > 1)
            return ($URI_GET[$this->dispatchId]);
        else {
            return "";
        }
    }

    function reParam($param) {
        if ($param[0] == '/')
            return str_replace("//", "/", $param);
        return '/' . $param;
    }

    /**
     * @return Symfony\Component\HttpFoundation\Request
     */
    public function getHttpRequest() {
        return $this->symfonyRequest;
    }

    /**
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function getHttpResponse() {
        return $this->symfonyResponse;
    }

    function run() {

        $this->symfonyRequest = SymfonyRequest::createFromGlobals();
        $this->symfonyResponse = SymfonyResponse::create();
        $first_key = $this->reParam($this->dispatch());
        $ctrl_key = strtolower($this->reParam(Route::isApp($first_key)));


        if (Route::$app_alias != "/") {
            $_GET["CTR_METHOD_PARAMS"] = str_ireplace(Route::$app_alias, "", "/" . $_GET["CATAPPA_URI_PARAMS"]);
        } else
            $_GET["CTR_METHOD_PARAMS"] = $_GET["CATAPPA_URI_PARAMS"];
        $clazz = Route::hasRoutable($ctrl_key);

        $isCallMethod = $this->runResult($clazz, $ctrl_key);
      
        if ($isCallMethod == false) {
            if ($this->isIncorrectHTTP)
                return false;

            $clazz = Route::hasRoutable("/");
            if (Route::$app_alias != "/") {
                $_GET["CTR_METHOD_PARAMS"] = str_ireplace(Route::$app_alias, "", "/" . $_GET["CATAPPA_URI_PARAMS"]);
            } else
                $_GET["CTR_METHOD_PARAMS"] = $_GET["CATAPPA_URI_PARAMS"];


            if (is_object($clazz)) {
                $method_result = $this->webMethod($clazz, $first_key);
                $this->runResult($method_result);
            }
            if (!$this->isMethodCall && !$result && $this->isIncorrectHTTP == false) {
                $nothing = Route::getNothing();
                $this->runResult($nothing, $first_key);
            }
        }
    }

    protected function runResult($result, $param) {

        if (!is_object($result))
            return false;
        if ($result instanceof Catappa\Http\Controller) {
            $this->isMethodCall = false;
            $method_result = $this->webMethod($result);
        } else
            $method_result = $result;
        if ($method_result instanceof Catappa\Response\View_Interface) {
            $method_result->render();
            return true;
        } else if ($method_result instanceof Catappa\Http\Controller) {
            return $this->runResult($method_result, $param);
        } else if (is_null($method_result) && Catappa\Collections\Config::getInstance()->model_auto_mode == TRUE) {
            http_response_code(204);
        } else if ($method_result instanceof Symfony\Component\HttpFoundation\Response) {
            $method_result->send();
        } else if (is_array($method_result)||$method_result instanceof \stdClass) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($method_result);
            die();
        } else if ($method_result instanceof Catappa\DataObject\Model || $method_result instanceof Catappa\Collections\ArrayList) {
            header('Content-Type: application/json; charset=utf-8');
            if ($method_result instanceof Catappa\DataObject\Model) {
                if ($this->symfonyRequest->getMethod() == "GET")
                    return $method_result->printJSON();
                if ($method_result->isValid() == false) {
                    http_response_code(207);
                    return $method_result->printMessagesJSON();
                } else {
                    if ($this->symfonyRequest->getMethod() == "POST")
                        http_response_code(201);
                    return $method_result->printJSON();
                }
            } else
                return $method_result->printJSON();
        }
        return $this->isMethodCall;
    }

    protected function webMethod($ctrl_obj, $param = null) {
        $http_method = $this->symfonyRequest->getMethod();
        if ($http_method == "POST" || $http_method == "PUT" || $http_method == "PATCH") {
            if ($http_method == "POST")
                $_INPUT = $_POST;
            else
                parse_str(file_get_contents('php://input'), $_INPUT);
            $GLOBALS["PUT"] = $_INPUT;
        }
        $result = $this->parseAnnotation($ctrl_obj, $param);

        if ($result == false)
            return false;

        $ctrl_method_name = $result["method"];
        $use_http_method = $result["http"];
        $uri_key_values = $result["values"];
        $ctrl_http_params = $result["params"];
        $send_method_params = array();

        $m = new ReflectionMethod(get_class($ctrl_obj), $ctrl_method_name);
        $ref_params = $m->getParameters();
        if (count($ref_params) > 0) {
            foreach ($ref_params as $par) {
                if ($par->getClass() !== NULL) {
                    $class = $par->getClass();
                    if ($class->name == "Symfony\Component\HttpFoundation\Response")
                        $send_method_params[$par->getPosition()] = $this->getHttpResponse();
                    else if ($class->name == "Symfony\Component\HttpFoundation\Request")
                        $send_method_params[$par->getPosition()] = $this->getHttpRequest();
                    else if ($class->name == "Catappa\Http\HttpRequest")
                        $send_method_params[$par->getPosition()] = $psr7Factory->createRequest($request);
                    else if ($class->name == "Catappa\Http\HttpResponse")
                        $send_method_params[$par->getPosition()] = $psr7Factory->createResponse($response);
                }
            }
        }

        if (($http_method != $use_http_method) && ($use_http_method != "ALL")) {
            $this->isIncorrectHTTP = true;
            return Route::callIncorrectHTTPMethod();
        }

        if (method_exists($ctrl_obj, $ctrl_method_name)) {
            foreach ($ctrl_http_params as $param) {
                $param = trim($param);
                if ($use_http_method == "POST" || $use_http_method == "PUT" || $use_http_method == "PATCH" || $use_http_method == "ALL") {
                    if (isset($_INPUT[$param]))
                        $send_method_params[$param] = $_INPUT[$param];
                    elseif (isset($_GET[$param]) && ($use_http_method == "ALL" || $use_http_method == "DELETE" || $use_http_method == "GET"))
                        $send_method_params[$param] = $_GET[$param];
                }
                else {
                    if (isset($_GET[$param]))
                        $send_method_params[$param] = $_GET[$param];
                }
            }

            $send_method_params = array_merge($uri_key_values, $send_method_params);
            $front_ctrl_class = Route::getAppFrontController();
            if ($front_ctrl_class != null) {
                $front_ctrl = ObjectFactory::getNewInstance($front_ctrl_class);
                $front_result = $front_ctrl->controller();
                if (!$front_result)
                    return false;
            }
            if (count($result["midles"]) > 0)
                Route::mergeMidleMap($result["midles"]);
            $midle_result = Route::runMiddleWares();

            if ($midle_result == false)
                return false;
            elseif ($midle_result instanceof Catappa\Http\Controller || $midle_result instanceof Catappa\Response\View_Interface || $midle_result instanceof Symfony\Component\HttpFoundation\Response)
                return $midle_result;
            $this->isMethodCall = true;
            return call_user_func_array(array($ctrl_obj, $ctrl_method_name), $send_method_params);
        }
    }

    protected function parseAnnotation($ctrl_obj, $x = null) {
        $ref = new \ReflectionObject($ctrl_obj);
        $http_method = $this->symfonyRequest->getMethod();
        $methods = $ref->getMethods();
        $ctrl_piece = Route::getClassUri(get_class($ctrl_obj));
        $search = strtolower($this->reParam($_GET["CTR_METHOD_PARAMS"]));
        $ctrl_pos = strpos($search, $ctrl_piece);
        if ($ctrl_pos !== false)
            $search = substr_replace($search, "/", $ctrl_pos, strlen($ctrl_piece));

        if ($ctrl_piece != "/")
            $_GET["CTR_METHOD_PARAMS"] = substr_replace($_GET["CTR_METHOD_PARAMS"], "", 0, strlen($ctrl_piece));

        $search = $this->reParam($search);

        $method_name = null;
        $result = array();
        $uri_arr = array_values(array_filter(explode("/", $_GET["CTR_METHOD_PARAMS"])));
        foreach ($methods as $m) {
            $method_name = $m->getName();

            preg_match_all("#(@[a-zA-Z]+\s*[a-zA-Z0-9, ()_].*)#", $m->getDocComment(), $ma);
            $send_method_params = array();
            $annoted_arr = array_filter($ma[1]);

            $lasturi_key = "";
            $isChange = false;

            foreach ($annoted_arr as $annoted) {
                $anot_param = array_filter(explode(" ", $annoted));

                $annotation = trim($anot_param[0]);
                $annotation_value = trim($anot_param[1]);

                if (isset(Catappa::$AVAILABLE_HTTP_MEHHODS[$annotation])) {
                    $result[$annotation_value] = array("http" => trim(Catappa::$AVAILABLE_HTTP_MEHHODS[$annotation]), "method" => $method_name, "uri" => $annotation_value);
                    $lasturi_key = $annotation_value;
                    $method_url = array_values(array_filter(explode("/", $annotation_value)));

                    $replacement_params = array();
                    $replacement_vals = array();
                    $method_params_vals = array();
                    foreach ($method_url as $key => $val) {

                        if (preg_match("/{+(.*?)}/", $val)) {
                            $replacement_params[] = $val;
                            $replacement_vals[] = $uri_arr[$key];
                            $param_name = trim(str_replace(array("{", "}"), array("", ""), $val));
                            $method_params_vals[$param_name] = $uri_arr[$key];
                            $isChange = true;
                        }
                    }
                    $new_uri = trim((str_replace($replacement_params, $replacement_vals, $annotation_value)));
                    $result[$lasturi_key]["reuri"] = $new_uri;
                    $result[$lasturi_key]["values"] = $method_params_vals;
                    $result[$new_uri] = $result[$lasturi_key];

                    if ($isChange)
                        unset($result[$lasturi_key]);
                    $lasturi_key = $new_uri;
                } elseif ($annotation == "@Params")
                    $result[$lasturi_key]["params"] = explode(",", $annotation_value);
                elseif ($annotation == "@Type")
                    $result[$lasturi_key]["type"] = $annotation_value;
                elseif ($annotation == "@Before")
                    $result[$lasturi_key]["midles"] = explode(",", $annotation_value);
            }

            if (strtolower($search) == strtolower($lasturi_key)) {
                if ($result[$lasturi_key]["http"] == $http_method) {
                    return $result[$lasturi_key];
                    continue;
                }
            }
        }

        if (array_key_exists($search, $result))
            return $result[$search];
        return false;
    }

    public static function loadHelpers(array $files) {
        $catppa_dir = dirname(dirname(__FILE__));
        foreach ($files as $file) {
            if (file_exists($catppa_dir . DS . "Helpers" . DS . $file . ".php"))
                require $catppa_dir . DS . "Helpers" . DS . $file . ".php";
            if (file_exists(Route::$app_path . DS . "Helpers" . DS . $file . ".php"))
                require Route::$app_path . DS . "Helpers" . DS . $file . ".php";
        }
    }

}
