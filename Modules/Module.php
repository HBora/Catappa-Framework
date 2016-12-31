<?php

namespace Catappa\Modules;

interface Module {
    public function init();
    public function onShow();
}

?>