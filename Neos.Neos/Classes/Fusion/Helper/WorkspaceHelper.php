<?php
namespace Neos\Neos\Fusion\Helper;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Projection\Content\ContentSubgraphInterface;
use Neos\ContentRepository\Domain\Projection\Workspace\Workspace;
use Neos\ContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\Flow\Annotations as Flow;

/**
 * The Workspace helper for EEL contexts
 */
class WorkspaceHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    /**
     * @param ContentSubgraphInterface $contentSubgraph
     * @return array|Workspace[]
     */
    public function getWorkspaceChain(ContentSubgraphInterface $contentSubgraph): array
    {
        /** @var Workspace $currentWorkspace */
        $currentWorkspace = $this->workspaceFinder->findOneByCurrentContentStreamIdentifier($contentSubgraph->getContentStreamIdentifier());
        $workspaceChain = [];
        while ($currentWorkspace instanceof Workspace) {
            $workspaceChain[(string)$currentWorkspace->getWorkspaceName()] = $currentWorkspace;
            $currentWorkspace = $currentWorkspace->getBaseWorkspaceName() ? $this->workspaceFinder->findOneByName($currentWorkspace->getBaseWorkspaceName()) : null;
        }

        return $workspaceChain;
    }

    /**
     * All methods are considered safe
     *
     * @param string $methodName
     * @return bool
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
