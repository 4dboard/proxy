<?php

/* Importing the wrapper class from the yxorP\http namespace. */

use yxorP\inc\constants;
use yxorP\inc\wrapper;

/* A class that extends the wrapper class. */

class snagHandlerAction extends wrapper
{
    /* A method that is called when an exception is thrown. */
    public function onBuildException($e): void
    {
        /* Calling the notifyException method on the Snag instance. */
        if (ENV_DEBUG || !(int)str_contains(constants::get(VAR_SERVER)[YXORP_SERVER_NAME], CHAR_PERIOD)) print_r($e);
        constants::get(VAR_BUGSNAG)?->notifyException($e);
    }
}