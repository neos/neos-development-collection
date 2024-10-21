<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeMigration\Transformation;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\UpdateRootNodeAggregateDimensions;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\NodeMigration\MigrationException;

class UpdateRootNodeAggregateDimensionsTransformationFactory implements TransformationFactoryInterface
{
    /**
     * @param array<string,string> $settings
     */
    public function build(
        array $settings,
        ContentRepository $contentRepository
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface {
        if (!isset($settings['nodeType'])) {
            throw new MigrationException(
                'The "nodeType" must not be empty.',
                1726754800
            );

        }
        try {
            $nodeTypeName = NodeTypeName::fromString($settings['nodeType']);
        } catch (\InvalidArgumentException $exception) {
            throw new MigrationException(
                sprintf('The given "nodeType" ("%s") is not valid.', $settings['nodeType']),
                1726754273
            );
        }
        return new class (
            $nodeTypeName,
            $contentRepository
        ) implements GlobalTransformationInterface {
            public function __construct(
                private readonly NodeTypeName $nodeTypeName,
                private readonly ContentRepository $contentRepository,
            ) {
            }

            public function execute(
                WorkspaceName $workspaceNameForWriting,
            ): void {

                $rootNodeAggregate = $this->contentRepository->getContentGraph($workspaceNameForWriting)->findRootNodeAggregateByType($this->nodeTypeName);

                if (!$rootNodeAggregate) {
                    throw new MigrationException(
                        sprintf('There is no root node with the given "nodeType" ("%s") in the content repository.', $this->nodeTypeName->value),
                        1726754019
                    );
                }

                $this->contentRepository->handle(
                    UpdateRootNodeAggregateDimensions::create(
                        $workspaceNameForWriting,
                        $rootNodeAggregate->nodeAggregateId
                    )
                );
            }
        };
    }
}
