<?php

namespace Catappa\Response\Layout;
/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */
/**
 * @name DefaultLayout
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package Response
 * @version 1.0
 * @category Response
 */
use Catappa\Response\Layout\LayoutInterface;

class DefaultLayout implements LayoutInterface {

    public $layout;

    const LAYOUT_CURRENT_VIEW = "LAYOUT_CURRENT_VIEW";

    public function __construct($shema = array()) {
        $this->layout = $shema;
    }

    public function get() {
        return $this->layout;
    }

    public function set($param) {
        $this->layout = $param;
    }

}
?>

