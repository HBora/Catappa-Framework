<?php

namespace Catappa\Modules\Composer;

use Composer\Script\Event;

class Create {

    public static function postUpdate(Event $event) {
        
    }

    public static function createAppSkeleton(Event $event) {

        $cur_path = getcwd();
        $project_name = ucfirst(basename($cur_path));
        $app_dir = $cur_path . DIRECTORY_SEPARATOR . "Apps" . DIRECTORY_SEPARATOR;
        $project_dir = $app_dir . $project_name;

        @mkdir($project_dir, 0777, true);
        @mkdir($project_dir . DIRECTORY_SEPARATOR . "Controllers", 0777, true);
        @mkdir($project_dir . DIRECTORY_SEPARATOR . "Layouts", 0777, true);
        @mkdir($project_dir . DIRECTORY_SEPARATOR . "Views", 0777, true);
        @mkdir($project_dir . DIRECTORY_SEPARATOR . "Middlewares", 0777, true);
        @mkdir($project_dir . DIRECTORY_SEPARATOR . "Models", 0777, true);


        file_put_contents($project_dir . DIRECTORY_SEPARATOR . "Route.php", '<?php
Route::add("/","Main");');

        file_put_contents($project_dir . DIRECTORY_SEPARATOR . "Config.php", '');
        $route = "";
        $route = file_get_contents($app_dir . "AppRoute.php");
        $route = $route . 'Route::addApp("/", function () {
    return "' . $project_name . '";
});
';
        file_put_contents($app_dir . "AppRoute.php", $route);

        file_put_contents($project_dir . DIRECTORY_SEPARATOR . "Controllers" . DIRECTORY_SEPARATOR . "Main.php", '<?php
namespace Apps\\' . $project_name . '\Controllers;
use Catappa\Http\Controller;
class Main implements Controller {
    
    /**
     * @GET /
     */
    function home() {
        echo "Welcome Catappa ' . $project_name . ' Project";
    }
}');


        file_put_contents($cur_path . DIRECTORY_SEPARATOR . "index.php", '<?php
session_start();
define("BASE_URL", "http://localhost/");
define("BASE_DIR", __DIR__);
define("DS", DIRECTORY_SEPARATOR);
require_once BASE_DIR . DS . "vendor" . DS . "autoload.php";
require_once __DIR__ . DS . "Kernel" . DS . "Catappa.php";
Catappa::getInstance()->run();
');
    }

}
