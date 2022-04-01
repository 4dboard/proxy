<?php /* yxorP */

namespace yxorP\inc;

use JetBrains\PhpStorm\NoReturn;
use pathinfo;
use strtok;
use yxorP\http;
use yxorP\Http\Response;

class Init
{
    public function __construct()
    {

        new Dot();
        foreach (array('helper', 'http') as $_asset) self::FILES_INC($_asset);

        require(@$GLOBALS['PLUGIN_DIR'] . '/plugin/AbstractPlugin.php');


        self::FILES_GET('override/' . @$GLOBALS['TARGET_HOST'] . '/assets/') || self::FILES_GET('override/default/assets/') || $proxy = new Http\Punisher();
        foreach (@$GLOBALS['PLUGINS'] as $plugin) $this->EXEC($proxy, $plugin);

        $_content = $proxy->forward(Http\Request::createFromGlobals(), @$GLOBALS['PROXY_URL'])->getContent();

        echo $_content;

        @$GLOBALS['CACHE_ADAPTER']->set(@$GLOBALS['CACHE_KEY'], str_replace(fgetcsv(fopen(@$GLOBALS['PLUGIN_DIR'] . '/override/default/search_rewrite.csv', 'r')),
            fgetcsv(fopen(@$GLOBALS['PLUGIN_DIR'] . '/override/default/replace_rewrite.csv', 'r')), $_content));

    }

    public static function FILES_INC($dir): void
    {
        foreach (scandir($fdir = @$GLOBALS['PLUGIN_DIR'] . '/' . $dir) as $x) if (strlen($x) > 3) (is_dir($x)) ? self::FILES_INC($fdir . '/' . $x) : require($fdir . '/' . $x);
    }

    public static function FILES_GET($dir): void
    {
        foreach (scandir($fdir = @$GLOBALS['PLUGIN_DIR'] . '/' . $dir) as $x) if (strlen($x) > 3) if (is_dir($x)) self::FILES_GET($fdir . '/' . $x); else if (str_contains(@$GLOBALS['REQUEST_URI'], $x)) {
            echo file_get_contents($fdir . '/' . $x);
            exit;
        }
    }

    private function EXEC($proxy, $plugin): void
    {
        if (file_exists(@$GLOBALS['PLUGIN_DIR'] . '/plugin/' . $plugin . '.php')) require(@$GLOBALS['PLUGIN_DIR'] . '/plugin/' . $plugin . '.php'); elseif (class_exists('\\yxorP\\plugin\\' . $plugin)) $plugin = '\\yxorP\\plugin\\' . $plugin;
        $proxy->addSubscriber(new $plugin());
    }

}


