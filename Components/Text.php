<?php

namespace Catappa\Components;

use Catappa\Components\Component;

class Text extends Component {

    public function startTag($param) {
        echo "<input type='text'  value='deneme'/>";
        
    }

    public function endTag($param) {
        
    }

}
