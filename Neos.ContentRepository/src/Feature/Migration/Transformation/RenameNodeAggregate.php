<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Migration\Transformation;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\Feature\NodeRenaming\Command\ChangeNodeAggregateName;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\SharedModel\Node\ReadableNodeAggregateInterface;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;

/**
 * Change the node type.
 */
class RenameNodeAggregate implements NodeAggregateBasedTransformationInterface
{
    protected NodeAggregateCommandHandler $nodeAggregateCommandHandler;

    /**
     * The new Node Name to use as a string
     */
    protected string $newNodeName;

    public function __construct(NodeAggregateCommandHandler $nodeAggregateCommandHandler)
    {
        $this->nodeAggregateCommandHandler = $nodeAggregateCommandHandler;
    }

    public function setNewNodeName(string $newNodeName): void
    {
        $this->newNodeName = $newNodeName;
    }

    public function execute(
        ReadableNodeAggregateInterface $nodeAggregate,
        ContentStreamIdentifier $contentStreamForWriting
    ): CommandResult {
        return $this->nodeAggregateCommandHandler->handleChangeNodeAggregateName(new ChangeNodeAggregateName(
            $contentStreamForWriting,
            $nodeAggregate->getIdentifier(),
            NodeName::fromString($this->newNodeName),
            UserIdentifier::forSystemUser()
        ));
    }
}
