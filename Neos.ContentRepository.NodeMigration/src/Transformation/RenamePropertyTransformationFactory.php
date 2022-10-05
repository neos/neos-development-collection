<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\NodeMigration\Transformation;

use Neos\ContentRepository\Core\CommandHandler\CommandResult;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetSerializedNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollectionInterface;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\SharedModel\User\UserId;

/**
 * Remove the property
 */
class RenamePropertyTransformationFactory implements TransformationFactoryInterface
{
    /**
     * @param array<string,string> $settings
     */
    public function build(
        array $settings,
        ContentRepository $contentRepository
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface
    {
        return new class (
            $settings['from'],
            $settings['to'],
            $contentRepository
        ) implements NodeBasedTransformationInterface {
            public function __construct(
                /**
                 * Property name to change
                 */
                private readonly string $from,
                /**
                 * New name of property
                 */
                private readonly string $to,
                private readonly ContentRepository $contentRepository
            )
            {
            }

            public function execute(
                Node $node,
                DimensionSpacePointSet $coveredDimensionSpacePoints,
                ContentStreamId $contentStreamForWriting
            ): ?CommandResult
            {
                if ($node->hasProperty($this->from)) {
                    /** @var PropertyCollectionInterface $properties */
                    $properties = $node->properties;
                    return $this->contentRepository->handle(
                        new SetSerializedNodeProperties(
                            $contentStreamForWriting,
                            $node->nodeAggregateId,
                            $node->originDimensionSpacePoint,
                            SerializedPropertyValues::fromArray([
                                $this->to => $properties->serialized()
                                    ->getProperty($this->from),
                                $this->from => null
                            ]),
                        )
                    );
                }

                return null;
            }
        };
    }
}
