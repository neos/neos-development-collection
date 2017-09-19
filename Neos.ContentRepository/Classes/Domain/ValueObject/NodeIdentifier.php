<?php

namespace Neos\ContentRepository\Domain\ValueObject;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Utility;
use Neos\Flow\Utility\Algorithms;

final class NodeIdentifier implements \JsonSerializable
{

    /**
     * @var string
     */
    private $identifier;

    public static function create(): NodeIdentifier
    {
        return new NodeIdentifier(Algorithms::generateUUID());
    }

    public static function forAutoCreatedChildNode(NodeName $childNodeName, NodeIdentifier $nodeIdentifier): NodeIdentifier
    {
        return new NodeIdentifier(Utility::buildAutoCreatedChildNodeIdentifier((string)$childNodeName, (string)$nodeIdentifier));
    }

    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }


    function jsonSerialize()
    {
        return $this->identifier;
    }

    public function __toString()
    {
        return $this->identifier;
    }

}