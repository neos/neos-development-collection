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
     * @return Workspace|null
     */
    public function findOneByName(WorkspaceName $name): ?Workspace
    {
        return $this->__call('findOneByWorkspaceName', [(string)$name]);
    }

    /**
     * @param WorkspaceName $prefix
     * @return array|Workspace[]
     * @throws \Neos\Flow\Persistence\Exception\InvalidQueryException
     */
    public function findByPrefix(WorkspaceName $prefix): array
    {
        $result = [];
        $query = $this->createQuery();
        foreach ($query->matching(
            $query->like('workspaceName', (string) $prefix . '%')
        )->execute() as $similarlyNamedWorkspace) {
            /** @var Workspace $similarlyNamedWorkspace */
            $result[(string) $similarlyNamedWorkspace->getWorkspaceName()] = $similarlyNamedWorkspace;
        }

        return $result;
    }
}
