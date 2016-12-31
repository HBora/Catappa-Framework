<?php
class Table extends \Annotation {
    public $name;
}

class Id extends \Annotation {
    
}

class Generated extends \Annotation {
    
}

class Column extends \Annotation {

    public $name;
    public $type;
    public $size;

}

class Relation extends \Annotation {

    public $mappedBy;
    public $fk;
    public $pk;

}

class NamedQuery extends \Annotation {

    public $name;
    public $query;

}

class StoredQuery extends \Annotation {

    public $name;
    public $query;

}

class OneToOne extends Relation {
    
}

class OneToMany extends Relation {
    
}

class ManyToMany extends Relation {
    
}

class ManyToOne extends Relation {
    
}

class Required extends \Annotation {
    public $message;

}

class Blank extends \Annotation {

    public $message = "Requried this fields";

}

class NotBlank extends \Annotation {

    public $message = "Requried this fields";

}

class Email extends \Annotation {

    public $message = "Requried this fields";
    public $checkMX = false, $checkHost = false;

}

class NotNull extends \Annotation {

    public $message = "Requried this fields";

}

class IsNull extends \Annotation {

    public $message = "Requried this fields";

}

class Type extends \Annotation {

    public $type = "", $message = "Requried this fields";

}

class Length extends \Annotation {

    public $message = "Requried this fields";
    public $min, $max, $charset, $minMessage, $maxMessage;

}

class Ip extends \Annotation {

    public $version = "", $message = "Requried this fields";

}

class Uuid extends \Annotation {

    public $strict, $version = "", $message = "Requried this fields";

}

class Url extends \Annotation {

    public $protocols, $checkDNS, $dnsMessage = "", $message = "Requried this fields";

}

class Regex extends \Annotation {

    public $pattern, $htmlPattern, $match = "", $message = "Requried this fields";

}

class UserPassword extends \Annotation {

    public $message = "Requried this fields";

}

class Choice extends \Annotation {

    public $message = "Requried this fields";
    public $choices, $callback, $multiple, $minMessage, $maxMessage;
    public $min, $max, $multipleMessage, $strict;

}

class DateAndTime extends \Annotation {

    public $message = "Requried this fields";
    public $format;

}

class Date extends \Annotation {

    public $message = "Requried this fields";
    public $format;

}

class Time extends \Annotation {

    public $message = "Requried this fields";

}

class EqualTo extends \Annotation {

    public $message = "Requried this fields";
    public $value;

}

class Iban extends \Annotation {

    public $message = "Requried this fields";

}

class CardScheme extends \Annotation {
    public $message = "Requried this fields";
    public $schemes;

}
?>
