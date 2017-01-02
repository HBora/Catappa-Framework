<?php

namespace Catappa\Response\Sass;

/*
 * This file is part of the Catappa package.
 *
 * (c) H.Bora ABACI <hboraabaci@gmail.com>
 */

/**
 * @name Sass
 * @author H.Bora Abacı
 * @copyright H.Bora Abacı
 * @package Response
 * @version 1.6
 * @category Response
 */
use Catappa\Patterns\Singleton;

class Sass extends Singleton {

    private $rendered = array();
    private $paterns = array('/{+\$(.*?)}/',
        '/(\s*)@(if|elseif|foreach|for|while)(\s*\(.*\))/',
        '/(\s*)@(endif|endforeach|endfor|endwhile|break|continue)(\s*)/',
        '/(\s*)@(else)(\s*)/',
        '/@include(\s*\(.*\))(\s*)/',
        '/@layout(\s*\(.*\))(\s*)/',
        '/@{+(.*?)}/',
        '/@(.*?)(\s*\(.*\))(\s*)/',
    );
    private $replace_paterns = array('<?php echo $$1;?>',
        '$1<?php $2$3: ?>',
        '$1<?php $2; ?>$3',
        '$1<?php $2: ?>$3',
        '<?php include $1;?>$2',
        '<?php include $1;?>$2',
        '<?php echo ${1};?>',
        '<?php $this->call("$1", array$2); ?>$3'
    );

    public static function getInstance() {
        return parent::getInstance(__CLASS__);
    }

    public static function addTerm($pattern, $replace) {
        Sass::$paterns[] = $pattern;
        Sass::$replace_paterns[] = $replace;
    }

    public function render($file) {

        if (array_search($file, $this->rendered))
            return;
        $this->rendered[] = $file;
        $buff = "";
        $file_dir = basename(dirname($file));
        $file_name = basename($file);
        if (file_exists($file)) {
            $buff = file_get_contents($file);
            $save_file_dir_name = \Route::$app_path . DS . "Views" . DS . "sasscache";
            if ($file_dir != "Views") {
                if (!file_exists($save_file_dir_name . DS . $file_dir))
                    mkdir($save_file_dir_name . DS . $file_dir, 0777, true);
                $save_file_dir_name.= DS . $file_dir;
            }
        }
        preg_match_all('/@layout(\s*\(.*\))(\s*)/', $buff, $layouts);
        foreach ($layouts[1] as $nfile) {
            $nfile = str_replace(array("('", "')"), array("", ""), $nfile);
            $nfile = \Route::$app_path . DS . "Views" . DS . strtolower(str_replace(array("\\", "/"), array(DS, DS), $nfile));
            $this->render($nfile);
        }

        $buff = preg_replace($this->paterns, $this->replace_paterns, $buff);
        file_put_contents($save_file_dir_name . DS . $file_name, $buff);
    }

}