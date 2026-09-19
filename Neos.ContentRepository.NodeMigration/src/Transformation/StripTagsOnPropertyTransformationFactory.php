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

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Strip all tags on a given property
 */
class StripTagsOnPropertyTransformationFactory implements TransformationFactoryInterface
{
    /**
     * @param array<string,string> $settings
     */
    public function build(
        array $settings,
        ContentRepository $contentRepository,
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface {
        return new class (
            $settings['property'],
        ) implements NodeBasedTransformationInterface {
            public function __construct(
                /**
                 * the name of the property to work on.
                 */
                private readonly string $propertyName,
            ) {
            }

            public function execute(
                Node $node,
                DimensionSpacePointSet $coveredDimensionSpacePoints,
                WorkspaceName $workspaceNameForWriting
            ): TransformationStep {
                $propertyValue = $node->properties[$this->propertyName];
                if ($propertyValue === null) {
                    return TransformationStep::createEmpty();
                }
                if (!is_string($propertyValue)) {
                    throw new \Exception(
                        sprintf('StripTagsOnProperty can only be applied to properties of type string. Property "%s" is of type %s', $this->propertyName, get_debug_type($propertyValue)),
                        1645391885
                    );
                }
                $newValue = strip_tags($propertyValue);
                return TransformationStep::fromCommand(
                    SetNodeProperties::create(
                        $workspaceNameForWriting,
                        $node->aggregateId,
                        $node->originDimensionSpacePoint,
                        PropertyValuesToWrite::fromArray([
                            $this->propertyName => $newValue,
                        ]),
                    )
                );
            }
        };
    }
}
