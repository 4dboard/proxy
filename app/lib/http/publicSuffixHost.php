<?php namespace yxorP\app\lib\http;

use yxorP\app\lib\parser\nameInterface;
use yxorP\app\yP;

class publicSuffixHost
{

    public function registrableDomain(): nameInterface
    {
        return yP::store(ENV_DEFAULT_HOST);
    }

}
