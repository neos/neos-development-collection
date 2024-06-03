<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeMigration\Transformation;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

final class Transformations
{
    /**
     * @var GlobalTransformationInterface[]
     */
    protected array $globalTransformations = [];

    /**
     * @var NodeAggregateBasedTransformationInterface[]
     */
    protected array $nodeAggregateBasedTransformations = [];

    /**
     * @var NodeBasedTransformationInterface[]
     */
    protected array $nodeBasedTransformations = [];

    /**
     * @codingStandardsIgnoreStart
     * @param array<int|string,GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface> $transformationObjects
     * @codingStandardsIgnoreEnd
     */
    public function __construct(array $transformationObjects)
    {
        foreach ($transformationObjects as $transformationObject) {
            if ($transformationObject instanceof GlobalTransformationInterface) {
                $this->globalTransformations[] = $transformationObject;
            } elseif ($transformationObject instanceof NodeAggregateBasedTransformationInterface) {
                $this->nodeAggregateBasedTransformations[] = $transformationObject;
            } elseif ($transformationObject instanceof NodeBasedTransformationInterface) {
                $this->nodeBasedTransformations[] = $transformationObject;
            } else {
                /** @var mixed $transformationObject */
                throw new \InvalidArgumentException(sprintf(
                    'Transformation object must implement either %s, %s or %s. Given: %s',
                    GlobalTransformationInterface::class,
                    NodeAggregateBasedTransformationInterface::class,
                    NodeBasedTransformationInterface::class,
                    is_object($transformationObject)
                        ? get_class($transformationObject)
                        : gettype($transformationObject)
                ), 1611735528);
            }
        }
    }

    public function containsGlobal(): bool
    {
        return count($this->globalTransformations) > 0;
    }

    public function containsNodeAggregateBased(): bool
    {
        return count($this->nodeAggregateBasedTransformations) > 0;
    }

    public function containsNodeBased(): bool
    {
        return count($this->nodeBasedTransformations) > 0;
    }

    public function containsMoreThanOneTransformationType(): bool
    {
        $nonEmptyTransformationTypes = 0;

        if ($this->containsGlobal()) {
            $nonEmptyTransformationTypes++;
        }

        if ($this->containsNodeAggregateBased()) {
            $nonEmptyTransformationTypes++;
        }

        if ($this->containsNodeBased()) {
            $nonEmptyTransformationTypes++;
        }

        return $nonEmptyTransformationTypes > 1;
    }

    public function executeGlobalAndBlock(
        WorkspaceName $workspaceNameForWriting,
    ): void {
        foreach ($this->globalTransformations as $globalTransformation) {
            $globalTransformation->execute($workspaceNameForWriting);
        }
    }

    public function executeNodeAggregateBasedAndBlock(
        NodeAggregate $nodeAggregate,
        WorkspaceName $workspaceNameForWriting,
        ContentStreamId $contentStreamForWriting
    ): void {
        foreach ($this->nodeAggregateBasedTransformations as $nodeAggregateBasedTransformation) {
            $nodeAggregateBasedTransformation->execute($nodeAggregate, $workspaceNameForWriting, $contentStreamForWriting);
        }
    }

    public function executeNodeBasedAndBlock(
        Node $node,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        WorkspaceName $workspaceNameForWriting,
        ContentStreamId $contentStreamForWriting
    ): void {
        foreach ($this->nodeBasedTransformations as $nodeBasedTransformation) {
            $nodeBasedTransformation->execute($node, $coveredDimensionSpacePoints, $workspaceNameForWriting, $contentStreamForWriting);
        }
    }
}
