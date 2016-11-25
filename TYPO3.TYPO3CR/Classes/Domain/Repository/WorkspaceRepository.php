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
}
