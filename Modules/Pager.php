<?php

namespace Catappa\Modules;

use Catappa\Modules\Module;

class Pager implements Module {

    private $start, $end, $page_size, $add_sql, $total_items;

    public function __construct($page_size, $total_items) {
        $this->page_size = $page_size;
        $this->total_items = $total_items;
        if (!isset($_GET["page"]))
            $current_page = 1;
        else if (!is_numeric($_GET["page"]))
            $current_page = 1;
        else
            $current_page = (int) ($_GET["page"]);
        if (!is_int($current_page))
            $current_page = 1;
        $this->start = ($current_page * $this->page_size) - $this->page_size;
        $this->end = $current_page * $this->page_size;
        $this->end = $this->start;
        $this->add_sql = " LIMIT " . $this->end . ",$this->page_size";
        return $add_sql;
    }

    function onShow() {

        include "Views" . DS . "Pager.php";
    }

    public function getLimitSql() {
        return $this->add_sql;
    }

    public function init() {
        
    }

    public function getCursor() {
        return $this->start;
    }

    public function getLimit() {
        return $this->page_size;
    }

}
