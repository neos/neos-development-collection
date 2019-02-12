<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Projection\Workspace;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
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
    private $cachedWorkspacesByName = [];

    /**
     * @var array
     */
    private $cachedWorkspacesByContentStreamIdentifier = [];

    /**
     * @param WorkspaceName $name
     * @return Workspace|null
     */
    public function findOneByName(WorkspaceName $name): ?Workspace
    {
        // TODO consider re-introducing runtime cache
        #if (!isset($this->cachedWorkspacesByName[(string)$name])) {
            $this->cachedWorkspacesByName[(string)$name] = $this->__call('findOneByWorkspaceName', [(string)$name]);
        #}
        return $this->cachedWorkspacesByName[(string)$name];
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @return Workspace|null
     */
    public function findOneByCurrentContentStreamIdentifier(ContentStreamIdentifier $contentStreamIdentifier): ?Workspace
    {
        // TODO consider re-introducing runtime cache
        #if (!isset($this->cachedWorkspacesByContentStreamIdentifier[(string)$contentStreamIdentifier])) {
            $this->cachedWorkspacesByContentStreamIdentifier[(string)$contentStreamIdentifier] = $this->__call('findOneByCurrentContentStreamIdentifier', [(string)$contentStreamIdentifier]);
        #}
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

    // TODO consider re-introducing runtime cache
//    public function resetCache()
//    {
//        $this->cachedWorkspacesByName = [];
//        $this->cachedWorkspacesByContentStreamIdentifier = [];
//    }

    public function getContentStreamIdentifierForWorkspace(WorkspaceName $workspaceName): ContentStreamIdentifier
    {
        $query = $this->createQuery();
        $query
            ->getQueryBuilder()
            ->select('e.currentContentStreamIdentifier')
            ->where('e.workspaceName = :workspaceName')
            ->setParameter('workspaceName', $workspaceName);
        $result = $query->execute()->getFirst();
        return ContentStreamIdentifier::fromString($result['currentContentStreamIdentifier']);
    }
}
