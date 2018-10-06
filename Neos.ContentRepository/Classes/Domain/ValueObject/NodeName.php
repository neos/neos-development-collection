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

use Neos\ContentRepository\Domain\Model\NodeInterface;

final class NodeName implements \JsonSerializable
{

    /**
     * @var NodeName
     */
    private static $rootNodeName;

    /**
     * the Root node does not have a name; so it is assigned this special NodeName
     *
     * @return NodeName
     */
    public static function root(): NodeName
    {
        if (!self::$rootNodeName) {
            self::$rootNodeName = new NodeName('-');
        }
        return self::$rootNodeName;
    }


    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        if (!is_string($name) || preg_match(NodeInterface::MATCH_PATTERN_NAME, $name) !== 1) {
            throw new \InvalidArgumentException('Invalid node name "' . $name . '" (a node name must only contain lowercase characters, numbers and the "-" sign).', 1364290748);
        }

        $this->name = $name;
    }

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->name;
    }

    public function isRoot(): bool
    {
        return $this === self::$rootNodeName;
    }
}
