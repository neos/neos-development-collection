<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\Migration\Dto;

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\ReadableNodeAggregateInterface;
use Neos\ContentRepository\Intermediary\Migration\Transformations\GlobalTransformationInterface;
use Neos\ContentRepository\Intermediary\Migration\Transformations\NodeAggregateBasedTransformationInterface;
use Neos\ContentRepository\Intermediary\Migration\Transformations\NodeBasedTransformationInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;

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
                throw new \RuntimeException('TODO: Transformation object must implement either GlobalTransformationInterface or NodeAggregateBasedTransformationInterface or NodeBasedTransformationInterface');
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

    public function executeGlobal(ContentStreamIdentifier $contentStreamForReading, ContentStreamIdentifier $contentStreamForWriting): CommandResult
    {
        $commandResult = CommandResult::createEmpty();
        foreach ($this->globalTransformations as $globalTransformation) {
            $commandResult = $commandResult->merge(
                $globalTransformation->execute($contentStreamForReading, $contentStreamForWriting)
            );
        }
        return $commandResult;
    }

    public function executeNodeAggregateBased(ReadableNodeAggregateInterface $nodeAggregate, ContentStreamIdentifier $contentStreamForWriting): CommandResult
    {
        $commandResult = CommandResult::createEmpty();
        foreach ($this->nodeAggregateBasedTransformations as $nodeAggregateBasedTransformation) {
            $commandResult = $commandResult->merge(
                $nodeAggregateBasedTransformation->execute($nodeAggregate, $contentStreamForWriting)
            );
        }
        return $commandResult;
    }

    public function executeNodeBased(NodeInterface $node, ContentStreamIdentifier $contentStreamForWriting): CommandResult
    {
        $commandResult = CommandResult::createEmpty();
        foreach ($this->nodeBasedTransformations as $nodeBasedTransformation) {
            $commandResult = $commandResult->merge(
                $nodeBasedTransformation->execute($node, $contentStreamForWriting)
            );
        }
        return $commandResult;
    }

}
