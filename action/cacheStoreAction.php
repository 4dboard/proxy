<?php
/* Importing the actionWrapper class from the yxorP\http namespace. */

use yxorP\http\cache;
use yxorP\action\actionWrapper;
use yxorP\inc\constants;

/* Extending the actionWrapper class, which is a class that allows you to hook into the request lifecycle. */

class cacheStoreAction extends actionWrapper
{
    /* A method that is called when the request is completed. */
    public function onCompleted(): void
    {
        /* Checking if the cache is valid, and if it is not, it is setting the cache to the response content. */
        if (!cache::cache()->isValid()) cache::cache()->set(constants::get(YXORP_RESPONSE)->getContent());
    }
}