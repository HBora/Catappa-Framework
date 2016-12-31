<?php

namespace Catappa\Response;

/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */

/**
 * @name View
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package Response
 * @version 1.2
 * @category Response
 */
use Catappa\Response\View_Interface;
use Catappa\Response\Layout\DefaultLayout;
use Symfony\Component\HttpFoundation\Response;

class View implements View_Interface {

    protected $map = array();
    protected $file_name;
    protected $header, $foother;
    protected $layout;
    protected $response = null;

    public function __construct($file_name, $data = array(), $layout = null) {
        $this->map = $data;
        $this->file_name = $file_name;
        $this->layout = $layout;

        if ($layout == null)
            $this->layout = new DefaultLayout(array(DefaultLayout::LAYOUT_CURRENT_VIEW));
    }

    /**
     * @return Symfony\Component\HttpFoundation\Response <Symfony\Component\HttpFoundation\Response >
     */
    public function getResponse() {
        if ($this->response == null)
            $this->response = new Response ();
        return $this->response;
    }

    public function setResponse(Response $response) {
        $this->response = $response;
    }

    public function setLayout(Layout $layout) {
        $this->layout = $layout;
    }

    public function getLayout() {
        return $this->layout;
    }

    public function add($key, &$value) {

        $this->map[$key] = &$value;
    }

    public function __set($name, $value) {

        $this->map[$name] = &$value;
    }

    public function __get($key) {
        return $this->map[$key];
    }

    public function &get($key) {
        return $this->map[$key];
    }

    public function delete($key) {
        unset($this->map[$key]);
    }

    public function render() {
        if ($this->response != null)
            ob_start();
        extract($this->map);
        foreach ($this->layout->layout as $file) {

            $xfile = $file;
            if ($file == DefaultLayout::LAYOUT_CURRENT_VIEW)
                $file = \Route::$app_path . DS . "Views" . DS . strtolower(str_replace(array("\\", "/"), array(DS, DS), $this->file_name)) . ".php";
            else
                $file = \Route::$app_path . DS . "Views" . DS . strtolower(str_replace(array("\\", "/"), array(DS, DS), $file));
            if (file_exists($file)) {
                include $file;
            } else {
                //echo $xfile . " View not found";
            }
        }
        if ($this->response != null) {
            $content = $this->response->getContent();
            $this->response->setContent($content . ob_get_clean());
            $this->response->send();
        }
    }

    function call($com, $values) {
        if (class_exists("Catappa\\Components\\$com")) {
            $obj = \Catappa\Patterns\ObjectFactory::getNewInstance("Catappa\\Components\\$com");
            $obj->startTag($values);
        } else if (class_exists(\Route::$app_package . "\\Components\\$com")) {
            $obj = \Catappa\Patterns\ObjectFactory::getNewInstance(\Route::$app_package . "\\Components\\$com");
            $obj->startTag($values);
        }
    }

}

