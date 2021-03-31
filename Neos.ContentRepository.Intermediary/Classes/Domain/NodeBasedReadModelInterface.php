<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\Domain;

/*
 * This file is part of the Neos.ContentRepository.Intermediary package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Intermediary\Domain\Feature\NodeIdentityInterface;
use Neos\ContentRepository\Intermediary\Domain\Feature\PropertyAccessInterface;
use Neos\ContentRepository\Intermediary\Domain\Feature\SubgraphTraversalInterface;
use Neos\ContentRepository\Intermediary\Domain\Feature\NodeMetadataInterface;

/**
 * The interface to be implemented by node based read models
 */
interface NodeBasedReadModelInterface extends NodeIdentityInterface, NodeMetadataInterface, PropertyAccessInterface, SubgraphTraversalInterface
{
}
