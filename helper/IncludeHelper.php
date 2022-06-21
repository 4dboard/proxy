<?php namespace yxorP\Helper;

use yxorP\http\Response;
use yxorP\http\Request;
use yxorP\http\ProxyEvent;

class IncludeHelper
{

    public function __construct()
    {
        foreach (file($GLOBALS['PLUGIN_DIR'] . '/.env') as $line) {
            if (trim(str_starts_with(trim($line), '#'))) continue;
            [$name, $value] = explode('=', $line, 2);
            $GLOBALS[$name] = str_replace("\r\n",null,$value);
        }

        require $GLOBALS['PLUGIN_DIR'] . '/setup/install.php';


        $GLOBALS['RESPONSE'] = $GLOBALS['RESPONSE'] ?: new Response();
        $GLOBALS['REQUEST'] = $GLOBALS['REQUEST'] ?: Request::createFromGlobals();

        \yxorP::FILES_CHECK($GLOBALS['SITE_CONTEXT']->DIR_FULL . '/assets', false);
        \yxorP::FILES_CHECK($GLOBALS['PLUGIN_DIR'] . '/override/global/assets', false);

        $GLOBALS['EVENT'] = $GLOBALS['EVENT'] ?: $GLOBALS['EVENT'] = new ProxyEvent(array('request' => $GLOBALS['REQUEST'], 'response' => $GLOBALS['RESPONSE']));
        $GLOBALS['GUZZLE'] = $GLOBALS['GUZZLE'] ?: new \GuzzleHttp\Client(['allow_redirects' => true, 'http_errors' => true, 'decode_content' => true, 'verify' => false, 'cookies' => true, 'idn_conversion' => true]);

    }



}