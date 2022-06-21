<?php

use yxorP\Http\EventWrapper;
use yxorP\http\ProxyEvent;
use yxorP\http\HtmlMinify;

class OverridePlugin extends EventWrapper
{
    public function onCompleted(ProxyEvent $event)
    {
        if ($GLOBALS['MIME'] !== 'text/html' && $GLOBALS['MIME'] !== 'application/javascript' && $GLOBALS['MIME'] !== 'text/css' && $GLOBALS['MIME'] !== 'application/xml' && !str_contains($GLOBALS['MIME'], 'text')) {
            return;
        }

        $GLOBAL_SEARCH_MERGE = $this->merge($this->merge($this->merge(\array_keys($GLOBALS['GLOBAL_REPLACE']?:[]), \array_keys($GLOBALS['SITE_CONTEXT']->SITE['replace']?:[]), array(preg_replace("#^[^:/.]*[:/]+#i", "", preg_replace("{/$}", "", urldecode($GLOBALS['SITE_CONTEXT']->TARGET_URL)))))));
        $GLOBAL_REPLACE_MERGE = $this->merge($this->merge($this->merge(\array_values($GLOBALS['GLOBAL_REPLACE']?:[]), \array_values($GLOBALS['SITE_CONTEXT']->SITE['replace'])?:[], array(preg_replace("#^[^:/.]*[:/]+#i", "", preg_replace("{/$}", "", urldecode($GLOBALS['SITE_CONTEXT']->SITE_URL)))))));
        $PATTERN_SEARCH_MERGE = array_filter($this->merge(\array_keys($GLOBALS['GLOBAL_PATTERN']?:[]), \array_keys($GLOBALS['SITE_CONTEXT']->SITE['pattern']?:[])));
        $PATTERN_REPLACE_MERGE = array_filter($this->merge(\array_values($GLOBALS['GLOBAL_PATTERN']?:[]), \array_values($GLOBALS['SITE_CONTEXT']->SITE['pattern']?:[])));
        $event['response']->setContent($this->REWRITE(str_replace($GLOBAL_SEARCH_MERGE, $GLOBAL_REPLACE_MERGE, preg_replace($PATTERN_SEARCH_MERGE, $PATTERN_REPLACE_MERGE, $event['response']->getContent()))));
    }

    public function merge($array1, $array2 = null, $array3 = null)
    {
        return (($array1 && is_array($array1) && $array2 && is_array($array2) && $array3 && is_array($array3))) ? array_filter(array_merge(array_merge((array)$array1, (array)$array2), (array)$array3), fn($value) => !is_null($value) && $value !== '') : (($array1 && is_array($array1) && $array2 && is_array($array2)) ? array_filter(array_merge((array)$array1, (array)$array2), fn($value) => !is_null($value) && $value !== '') : array_filter((array)$array1, fn($value) => !is_null($value) && $value !== ''));
    }

    public function REWRITE($content): string
    {
        return ($GLOBALS['MIME'] === 'text/html') ? preg_replace_callback("(<x>(.*?)</x>)", static function ($m) {
            return str_replace(yxorP::CSV($GLOBALS['PLUGIN_DIR'] . '/override/global/includes/search_rewrite.csv'),yxorP::CSV($GLOBALS['PLUGIN_DIR'] . '/override/global/includes/replace_rewrite.csv'), $m[1]);
        }, ((new HtmlMinify($content))->process())) : $content;
    }
}

