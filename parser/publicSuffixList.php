<?php declare(strict_types=1);

namespace Pdp;
interface publicSuffixList extends domainNameResolver
{
    public function getCookieDomain(host $host): resolvedDomainName;

    public function getICANNDomain(host $host): resolvedDomainName;

    public function getPrivateDomain(host $host): resolvedDomainName;
}