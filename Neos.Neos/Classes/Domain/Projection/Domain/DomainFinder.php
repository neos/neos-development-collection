<?php
namespace Neos\Neos\Domain\Projection\Domain;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcing\Projection\Doctrine\AbstractDoctrineFinder;
use Neos\Neos\Domain\ValueObject\DomainPort;
use Neos\Neos\Domain\ValueObject\HostName;
use Neos\Neos\Domain\ValueObject\UriScheme;

/**
 * Domain Finder
 */
final class DomainFinder extends AbstractDoctrineFinder
{
    /**
     * @param HostName $name
     * @return mixed
     */
    public function findOneByHostName(HostName $name) : ?Domain
    {
        return $this->__call('findOneByHostName', [(string)$name]);
    }

    /**
     * @param HostName $hostName
     * @param UriScheme $uriScheme
     * @param DomainPort $domainPort
     * @return Domain|null
     */
    public function findOneByHostNameSchemeAndPort(HostName $hostName, UriScheme $uriScheme = null, DomainPort $domainPort = null): ?Domain
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('hostName', (string)$hostName),
                $query->equals('uriScheme', $uriScheme),
                $query->equals('domainPort', $domainPort)
            )
        );

        return $query->execute()->getFirst();
    }
}
