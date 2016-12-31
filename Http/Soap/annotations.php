<?php

class WebMethod extends \Annotation {
    public $return;
    public $type;
    public $minOccurs;
    public $maxOccurs;
    public $suffix;
}

class Property extends \Annotation {
    public $type;
    public $minOccurs;
    public $maxOccurs;
    public $suffix;
}

class Param extends \Annotation {

    public $type;
    public $minOccurs;
    public $maxOccurs;
    public $suffix;

}

