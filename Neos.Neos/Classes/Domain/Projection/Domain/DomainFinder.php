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
use Neos\Neos\Domain\ValueObject\SchemeHostPort;
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
     * @param SchemeHostPort $schemeHostPort
     * @return Domain|null
     */
    public function findOneBySchemeHostAndPort(SchemeHostPort $schemeHostPort): ?Domain
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->equals('hostName', $schemeHostPort->getHostName()),
                $query->equals('uriScheme', $schemeHostPort->getUriScheme()),
                $query->equals('domainPort', $schemeHostPort->getDomainPort())
            )
        );

        return $query->execute()->getFirst();
    }
}
