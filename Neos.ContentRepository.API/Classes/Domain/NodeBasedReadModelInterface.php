<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Api\Domain;

/*
 * This file is part of the Neos.ContentRepository.Api package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Api\Domain\Feature\NodeIdentityInterface;
use Neos\ContentRepository\Api\Domain\NodeMetadataInterface;
use Neos\ContentRepository\Api\Domain\Feature\PropertyAccessInterface;
use Neos\ContentRepository\Api\Domain\Feature\SubgraphTraversalInterface;

/**
 * The interface to be implemented by node based read models
 */
interface NodeBasedReadModelInterface extends NodeIdentityInterface, NodeMetadataInterface, PropertyAccessInterface, SubgraphTraversalInterface
{
}
