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

/**
 * NodeIdentifier
 */
use Neos\Cache\CacheAwareInterface;
use Neos\Flow\Utility\Algorithms;

final class NodeIdentifier implements \JsonSerializable, CacheAwareInterface
{
    /**
     * A preg pattern to match against node identifiers
     */
    const PATTERN = '/^([a-z0-9\-]{1,255})$/';

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @param NodeName $childNodeName
     * @param NodeIdentifier $nodeIdentifier
     * @return NodeIdentifier
     */
    public static function forAutoCreatedChildNode(NodeName $childNodeName, NodeIdentifier $nodeIdentifier): NodeIdentifier
    {
        return new NodeIdentifier(Utility::buildAutoCreatedChildNodeIdentifier((string)$childNodeName, (string)$nodeIdentifier));
    }

    public function __construct(string $identifier)
    {
        if (!preg_match(self::PATTERN, $identifier)) {
            throw new \InvalidArgumentException('Invalid node identifier "' . $identifier . '" (a node identifier must only contain lowercase characters, numbers and the "-" sign).', 1505805774);
        }
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

    public function getCacheEntryIdentifier(): string
    {
        return $this->identifier;
    }
}
