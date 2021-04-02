<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\Migration\Transformations;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\ChangeNodeAggregateName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\ChangeNodeAggregateType;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\ReadableNodeAggregateInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\CommandResult;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\Flow\Annotations as Flow;

/**
 * Change the node type.
 */
class RenameNodeAggregate implements NodeAggregateBasedTransformationInterface
{

    /**
     * @Flow\Inject
     * @var NodeAggregateCommandHandler
     */
    protected $nodeAggregateCommandHandler;

    /**
     * The new Node Name to use as a string
     *
     * @var string
     */
    protected $newNodeName;

    /**
     * @param string $newNodeName
     */
    public function setNewNodeName(string $newNodeName): void
    {
        $this->newNodeName = $newNodeName;
    }

    public function execute(ReadableNodeAggregateInterface $nodeAggregate, ContentStreamIdentifier $contentStreamForWriting): CommandResult
    {
        return $this->nodeAggregateCommandHandler->handleChangeNodeAggregateName(new ChangeNodeAggregateName(
            $contentStreamForWriting,
            $nodeAggregate->getIdentifier(),
            NodeName::fromString($this->newNodeName),
            UserIdentifier::forSystemUser()
        ));
    }
}
