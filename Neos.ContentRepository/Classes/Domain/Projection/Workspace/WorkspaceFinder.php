<?php
namespace Neos\ContentRepository\Domain\Projection\Workspace;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcing\Projection\Doctrine\AbstractDoctrineFinder;

/**
 * Workspace Finder
 */
final class WorkspaceFinder extends AbstractDoctrineFinder
{
    /**
     * @param WorkspaceName $name
     * @return mixed
     */
    public function findOneByName(WorkspaceName $name)
    {
        return $this->__call('findOneByWorkspaceName', [(string)$name]);
    }
}
