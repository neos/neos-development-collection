<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Tests\Behavior\Features\Helper;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\ImmutableArrayObject;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\Nodes;

/**
 * The node discriminator value object collection
 */
final class NodeDiscriminators extends ImmutableArrayObject
{
    private function __construct(array $discriminators)
    {
        parent::__construct($discriminators);
    }

    public static function fromJsonString(string $jsonString): self
    {
        $discriminators = \json_decode($jsonString, true);

        return self::fromArray($discriminators);
    }

    public static function fromArray(array $array): self
    {
        return new self(array_map(
            function (string $shorthand) {
                return NodeDiscriminator::fromShorthand($shorthand);
            },
            $array
        ));
    }

    public static function fromNodes(Nodes $nodes): self
    {
        return new self(array_map(
            function (NodeInterface $node) {
                return NodeDiscriminator::fromNode($node);
            },
            $nodes->getArrayCopy()
        ));
    }

    public function equal(NodeDiscriminators $other): bool
    {
        return $this->getArrayCopy() == $other->getArrayCopy();
    }

    public function areSimilarTo(NodeDiscriminators $other): bool
    {
        $theseDiscriminators = $this->getArrayCopy();
        sort($theseDiscriminators);
        $otherDiscriminators = $other->getArrayCopy();
        sort($otherDiscriminators);

        return $theseDiscriminators == $otherDiscriminators;
    }

    /**
     * @param mixed $key
     * @return NodeInterface|false
     */
    public function offsetGet($key)
    {
        return parent::offsetGet($key);
    }

    /**
     * @return array|NodeInterface[]
     */
    public function getArrayCopy(): array
    {
        return parent::getArrayCopy();
    }

    /**
     * @return \ArrayIterator|NodeInterface[]
     */
    public function getIterator(): \ArrayIterator
    {
        return parent::getIterator();
    }
}
