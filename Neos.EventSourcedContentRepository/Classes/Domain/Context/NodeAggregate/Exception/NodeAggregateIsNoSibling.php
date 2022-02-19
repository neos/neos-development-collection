<?php

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\Flow\Annotations as Flow;

/**
 * The exception to be thrown if a node aggregate is no sibling of a reference node aggregate
 */
#[Flow\Proxy(false)]
final class NodeAggregateIsNoSibling extends \DomainException
{
    public static function butWasSupposedToBe(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        NodeAggregateIdentifier $referenceNodeAggregateIdentifier
    ): NodeAggregateIsNoSibling {
        return new self(
            'Node aggregate "' . $nodeAggregateIdentifier . '" is no sibling of "'
                . $referenceNodeAggregateIdentifier . '" but was supposed to be',
            1571068801
        );
    }
}
