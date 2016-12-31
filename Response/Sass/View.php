<?php

namespace Catappa\Response\Sass;

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
use Catappa\Response\View as V;
use Catappa\Response\Layout\DefaultLayout;

class View extends V {

    public function __construct($file_name, $data = array(), $layout = null) {

        parent::__construct($file_name, $data, $layout);
        $this->map["BASE_URL"] = BASE_URL;
    }

    public function render() {
        if (SASS_RUNTIME_COMPILE == FALSE)
            return $this->rendered();
        if ($this->response != null)
            ob_start();
        extract($this->map);
        $sass = Sass::getInstance();
        $curr_file = "";

        foreach ($this->layout->layout as $file) {

            if ($file == DefaultLayout::LAYOUT_CURRENT_VIEW) {
                $file_full_dir = \Route::$app_path . DS . "Views" . DS . strtolower(str_replace(array("\\", "/"), array(DS, DS), $this->file_name)) . ".php";
                $curr_file = strtolower(str_replace(array("\\", "/"), array(DS, DS), $this->file_name)) . ".php";
            } else {
                $file_full_dir = \Route::$app_path . DS . "Views" . DS . strtolower(str_replace(array("\\", "/"), array(DS, DS), $file));
                $curr_file = strtolower(str_replace(array("\\", "/"), array(DS, DS), $file));
            }
            $sass->render($file_full_dir);
            $compiled_file = \Route::$app_path . DS . "Views" . DS . "sasscache" . DS . $curr_file;

            if (file_exists($compiled_file)) {
                require_once $compiled_file;
            }
        }
        if ($this->response != null) {
            $content = $this->response->getContent();
            $this->response->setContent($content . ob_get_clean());
            $this->response->send();
        }
    }

    private function rendered() {

        if ($this->response != null)
            ob_start();
        extract($this->map);
        foreach ($this->layout->layout as $file) {
            if ($file == DefaultLayout::LAYOUT_CURRENT_VIEW)
                $file = \Route::$app_path . DS . "Views" . DS . "sasscache" . DS . strtolower(str_replace(array("\\", "/"), array(DS, DS), $this->file_name)) . ".php";
            else
                $file = \Route::$app_path . DS . "Views" . DS . "sasscache" . DS . strtolower(str_replace(array("\\", "/"), array(DS, DS), $file));
            if (file_exists($file)) {
                require_once $file;
            }
        }
        if ($this->response != null) {
            $content = $this->response->getContent();
            $this->response->setContent($content . ob_get_clean());
            $this->response->send();
        }
    }

}
