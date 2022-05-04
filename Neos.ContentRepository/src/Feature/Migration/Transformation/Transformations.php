<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Migration\Transformation;

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\ReadableNodeAggregateInterface;
use Neos\ContentRepository\Feature\Migration\Transformation\GlobalTransformationInterface;
use Neos\ContentRepository\Feature\Migration\Transformation\NodeAggregateBasedTransformationInterface;
use Neos\ContentRepository\Feature\Migration\Transformation\NodeBasedTransformationInterface;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;

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
        ContentStreamIdentifier $contentStreamForReading,
        ContentStreamIdentifier $contentStreamForWriting
    ): CommandResult {
        $commandResult = CommandResult::createEmpty();
        foreach ($this->globalTransformations as $globalTransformation) {
            $commandResult = $commandResult->merge(
                $globalTransformation->execute($contentStreamForReading, $contentStreamForWriting)
            );
        }
        return $commandResult;
    }

    public function executeNodeAggregateBased(
        ReadableNodeAggregateInterface $nodeAggregate,
        ContentStreamIdentifier $contentStreamForWriting
    ): CommandResult {
        $commandResult = CommandResult::createEmpty();
        foreach ($this->nodeAggregateBasedTransformations as $nodeAggregateBasedTransformation) {
            $commandResult = $commandResult->merge(
                $nodeAggregateBasedTransformation->execute($nodeAggregate, $contentStreamForWriting)
            );
        }
        return $commandResult;
    }

    public function executeNodeBased(
        NodeInterface $node,
        DimensionSpacePointSet $coveredDimensionSpacePoints,
        ContentStreamIdentifier $contentStreamForWriting
    ): CommandResult {
        $commandResult = CommandResult::createEmpty();
        foreach ($this->nodeBasedTransformations as $nodeBasedTransformation) {
            $commandResult = $commandResult->merge(
                $nodeBasedTransformation->execute($node, $coveredDimensionSpacePoints, $contentStreamForWriting)
            );
        }
        return $commandResult;
    }
}
