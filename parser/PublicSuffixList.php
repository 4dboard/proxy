<?php declare(strict_types=1);namespace Pdp;interface PublicSuffixList extends DomainNameResolver{public function getCookieDomain(Host $host):ResolvedDomainName;public function getICANNDomain(Host $host):ResolvedDomainName;public function getPrivateDomain(Host $host):ResolvedDomainName;}