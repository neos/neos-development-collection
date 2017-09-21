<?php
namespace Neos\Neos\Domain\Projection\Site;

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
use Neos\Neos\Domain\ValueObject\NodeName;

/**
 * Site Finder
 */
final class SiteFinder extends AbstractDoctrineFinder
{
    /**
     * @param NodeName $nodeName
     * @return Site|null
     */
    public function findOneByNodeName(NodeName $nodeName) : ?Site
    {
        return $this->__call('findOneByNodeName', [(string)$nodeName]);
    }
}
