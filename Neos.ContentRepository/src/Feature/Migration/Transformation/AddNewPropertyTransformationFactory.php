<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Migration\Transformation;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Feature\NodeModification\Command\SetSerializedNodeProperties;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValue;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

class AddNewPropertyTransformationFactory implements TransformationFactoryInterface
{
    public function __construct(private readonly NodeAggregateCommandHandler $nodeAggregateCommandHandler)
    {
    }

    public function build(array $settings): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface
    {
        return new class(
            $settings['newPropertyName'],
            $settings['type'],
            $settings['serializedValue'],
            $this->nodeAggregateCommandHandler
        ) implements NodeBasedTransformationInterface {
            public function __construct(
                /**
                 * Sets the name of the new property to be added.
                 */
                private readonly string $newPropertyName,
                private readonly string $type,
                /**
                 * Serialized Property value to be set.
                 */
                private readonly mixed $serializedValue,
                private readonly NodeAggregateCommandHandler $nodeAggregateCommandHandler,
            )
            {
            }

            public function execute(NodeInterface $node, DimensionSpacePointSet $coveredDimensionSpacePoints, ContentStreamIdentifier $contentStreamForWriting): CommandResult
            {
                if (!$node->hasProperty($this->newPropertyName)) {
                    return $this->nodeAggregateCommandHandler->handleSetSerializedNodeProperties(
                        new SetSerializedNodeProperties(
                            $contentStreamForWriting,
                            $node->getNodeAggregateIdentifier(),
                            $node->getOriginDimensionSpacePoint(),
                            SerializedPropertyValues::fromArray([
                                $this->newPropertyName => new SerializedPropertyValue($this->serializedValue, $this->type)
                            ]),
                            UserIdentifier::forSystemUser()
                        )
                    );
                }

                return CommandResult::createEmpty();
            }
        };
    }
}
