<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Domain\NodeAggregate\Exception;

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

/**
 * The exception to be thrown if an invalid node aggregate identifier was tried to be instantiated
 *
 * @Flow\Proxy(false)
 */
final class NodeAggregateIdentifierIsInvalid extends \DomainException
{
}
