<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeMigration\Transformation;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
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

    public function executeGlobal(
        WorkspaceName $workspaceNameForReading,
        WorkspaceName $workspaceNameForWriting,
    ): TransformationSteps {
        $transformationSteps = TransformationSteps::createEmpty();
        foreach ($this->globalTransformations as $globalTransformation) {
            $transformationSteps = $transformationSteps->withAppended(
                $globalTransformation->execute($workspaceNameForReading, $workspaceNameForWriting)
            );
        }
        return $transformationSteps;
    }

    public function executeNodeAggregateBased(
        NodeAggregate $nodeAggregate,
        WorkspaceName $workspaceNameForWriting
    ): TransformationSteps {
        $transformationSteps = TransformationSteps::createEmpty();
        foreach ($this->nodeAggregateBasedTransformations as $nodeAggregateBasedTransformation) {
            $transformationSteps = $transformationSteps->withAppended(
                $nodeAggregateBasedTransformation->execute($nodeAggregate, $workspaceNameForWriting)
            );
        }
        return $transformationSteps;
    }

    public function executeNodeBased(
        Node $node,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        WorkspaceName $workspaceNameForWriting
    ): TransformationSteps {
        $transformationSteps = TransformationSteps::createEmpty();
        foreach ($this->nodeBasedTransformations as $nodeBasedTransformation) {
            $transformationSteps = $transformationSteps->withAppended(
                $nodeBasedTransformation->execute($node, $coveredDimensionSpacePoints, $workspaceNameForWriting)
            );
        }
        return $transformationSteps;
    }
}
