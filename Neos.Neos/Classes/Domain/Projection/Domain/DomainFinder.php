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
use Neos\Neos\Domain\ValueObject\HostName;

/**
 * Workspace Finder
 */
final class DomainFinder extends AbstractDoctrineFinder
{
    /**
     * @param HostName $name
     * @return mixed
     */
    public function findOneByHostname(HostName $name)
    {
        return $this->__call('findOneByHostname', [(string)$name]);
    }
}
