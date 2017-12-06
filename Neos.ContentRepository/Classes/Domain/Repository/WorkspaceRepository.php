<?php
namespace Neos\ContentRepository\Domain\Repository;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\Repository;

/**
 * The repository for workspaces
 *
 * @Flow\Scope("singleton")
 */
class WorkspaceRepository extends Repository
{
    /**
     * @var array
     */
    protected $defaultOrderings = array(
        'baseWorkspace' => QueryInterface::ORDER_ASCENDING,
        'title' => QueryInterface::ORDER_ASCENDING
    );

    public function getPersonalWorkspaceByOwnerIdentifier($ownerIdentifier)
    {
        $query = $this->createQuery();
        $query->matching(
            $query->logicalAnd(
                $query->like('name', Workspace::PERSONAL_WORKSPACE_PREFIX . '%'),
                $query->equals('owner', $ownerIdentifier)
            )
        );

        return $query->execute()->getFirst();
    }
}
