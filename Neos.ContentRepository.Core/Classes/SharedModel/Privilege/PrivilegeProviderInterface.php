<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Privilege;

use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

interface PrivilegeProviderInterface
{
    public function isCommandAllowed(CommandInterface $command): bool;

    public function isFetchingOfNodesAllowedInSubgraph(ContentStreamId $contentStreamId, DimensionSpacePoint $dimensionSpacePoint): bool;
}
