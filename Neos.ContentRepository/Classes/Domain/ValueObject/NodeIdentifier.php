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
use Neos\Cache\CacheAwareInterface;
use Neos\ContentRepository\Utility;
use Ramsey\Uuid\Uuid;

/**
 * NodeIdentifier
 */
final class NodeIdentifier implements \JsonSerializable, CacheAwareInterface
{
    /**
     * A preg pattern to match against node identifiers
     */
    const PATTERN = '/^([a-z0-9\-]{1,255})$/';

    /**
     * @var string
     */
    private $identifier;

    /**
     * NodeIdentifier constructor.
     *
     * @param string $existingIdentifier
     */
    public function __construct(string $existingIdentifier = null)
    {
        if ($existingIdentifier !== null) {
            $this->setIdentifier($existingIdentifier);
        } else {
            $this->setIdentifier((string)Uuid::uuid4());
        }
    }

    /**
     * @param string $identifier
     */
    private function setIdentifier(string $identifier)
    {
        if (!preg_match(self::PATTERN, $identifier)) {
            throw new \InvalidArgumentException('Invalid node identifier "' . $identifier . '" (a node identifier must only contain lowercase characters, numbers and the "-" sign).', 1505840197862);
        }
        $this->identifier = $identifier;
    }

    /**
     * @param string $identifier
     * @return static
     */
    public static function fromString(string $identifier)
    {
        return new NodeIdentifier($identifier);
    }

    /**
     * @param NodeName $childNodeName
     * @param NodeIdentifier $nodeIdentifier
     * @return NodeIdentifier
     */
    public static function forAutoCreatedChildNode(NodeName $childNodeName, NodeIdentifier $nodeIdentifier): NodeIdentifier
    {
        return new NodeIdentifier(Utility::buildAutoCreatedChildNodeIdentifier((string)$childNodeName, (string)$nodeIdentifier));
    }

    /**
     * @return string
     */
    function jsonSerialize()
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getCacheEntryIdentifier(): string
    {
        return $this->identifier;
    }
}
