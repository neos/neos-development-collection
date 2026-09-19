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
 * Remove the property
 */
class RenamePropertyTransformationFactory implements TransformationFactoryInterface
{
    /**
     * @param array<string,string> $settings
     */
    public function build(
        array $settings,
        ContentRepository $contentRepository,
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface
    {
        return new class (
            $settings['from'],
            $settings['to'],
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
            )
            {
            }

            public function execute(
                Node $node,
                DimensionSpacePointSet $coveredDimensionSpacePoints,
                WorkspaceName $workspaceNameForWriting
            ): TransformationStep
            {
                $propertyValue = $node->properties[$this->from];
                if ($propertyValue === null) {
                    return TransformationStep::createEmpty();
                }
                return TransformationStep::fromCommand(
                    SetNodeProperties::create(
                        $workspaceNameForWriting,
                        $node->aggregateId,
                        $node->originDimensionSpacePoint,
                        PropertyValuesToWrite::fromArray([
                            $this->to => $propertyValue,
                            $this->from => null,
                        ]),
                    )
                );
            }
        };
    }
}
