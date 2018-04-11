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

use Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcing\Projection\Doctrine\AbstractDoctrineFinder;
use Neos\Flow\Annotations as Flow;

/**
 * Workspace Finder
 * @Flow\Scope("singleton")
 */
final class WorkspaceFinder extends AbstractDoctrineFinder
{

    /**
     * @var array
     */
    protected $cachedWorkspacesByName = [];

    /**
     * @var array
     */
    protected $cachedWorkspacesByContentStreamIdentifier = [];

    /**
     * @param WorkspaceName $name
     * @return Workspace|null
     */
    public function findOneByName(WorkspaceName $name): ?Workspace
    {
        if (!isset($this->cachedWorkspacesByName[(string)$name])) {
            $this->cachedWorkspacesByName[(string)$name] = $this->__call('findOneByWorkspaceName', [(string)$name]);
        }
        return $this->cachedWorkspacesByName[(string)$name];
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @return Workspace|null
     */
    public function findOneByCurrentContentStreamIdentifier(ContentStreamIdentifier $contentStreamIdentifier): ?Workspace
    {
        if (!isset($this->cachedWorkspacesByContentStreamIdentifier[(string)$contentStreamIdentifier])) {
            $this->cachedWorkspacesByContentStreamIdentifier[(string)$contentStreamIdentifier] = $this->__call('findOneByCurrentContentStreamIdentifier', [(string)$contentStreamIdentifier]);
        }
        return $this->cachedWorkspacesByContentStreamIdentifier[(string)$contentStreamIdentifier];
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
