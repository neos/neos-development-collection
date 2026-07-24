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

use Neos\ContentRepository\Core\CommandHandler\Commands;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\NodeModification\Command\SetNodeProperties;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Change a node property value from a scalar to a backed enum
 */
class ChangePropertyTypeFromScalarToBackedEnumTransformationFactory implements TransformationFactoryInterface
{
    /**
     * @param array{property: string} $settings
     */
    public function build(
        array $settings,
        ContentRepository $contentRepository,
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface {
        return new class (
            $settings['property'],
            $contentRepository->getNodeTypeManager(),
        ) implements NodeAggregateBasedTransformationInterface {
            public function __construct(
                /**
                 * Name of the property to be transformed
                 */
                private readonly string $property,
                private readonly NodeTypeManager $nodeTypeManager,
            ) {
            }

            public function execute(
                NodeAggregate $nodeAggregate,
                WorkspaceName $workspaceNameForWriting
            ): TransformationStep {
                /** @var class-string<\BackedEnum> $newType */
                $newType = $this->nodeTypeManager->getNodeType($nodeAggregate->nodeTypeName)
                    ?->getPropertyType($this->property);
                $commands = [];
                foreach ($nodeAggregate->getNodes() as $node) {
                    $propertyValue = $node->properties[$this->property];
                    if ($propertyValue === null) {
                        continue;
                    }
                    $commands[] = SetNodeProperties::create(
                        $workspaceNameForWriting,
                        $node->aggregateId,
                        $node->originDimensionSpacePoint,
                        PropertyValuesToWrite::fromArray([
                            $this->property => $newType::from($propertyValue),
                        ]),
                    );
                }

                return TransformationStep::fromCommands(Commands::fromArray($commands));
            }
        };
    }
}
