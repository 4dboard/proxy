<?php
/* Importing the Cache class from the Cache namespace. */

use yxorP\Cache\Cache;
use yxorP\inc\ActionWrapper;

/* Importing the ActionWrapper class from the yxorP\http namespace. Extending the ActionWrapper class, which is a class that is used to wrap events. */

class readyCheckAction extends ActionWrapper
{
    /* A function that is called when the event is checked. */
    public function onCheck(): string
    {
        /* Checking if the cache is valid, and if it is, it returns the cached data. */
        if (Cache::cache()->isValid()) return Cache::cache()->get(); //Todo: Call final event
    }
}