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

class RenameNodeAggregateTransformationFactory implements TransformationFactoryInterface
{
    public function __construct(private readonly NodeAggregateCommandHandler $nodeAggregateCommandHandler)
    {
    }

    /**
     * @param array<string,string> $settings
     */
    public function build(
        array $settings
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface {
        $newNodeName = $settings['newNodeName'];

        return new class(
            $newNodeName,
            $this->nodeAggregateCommandHandler
        ) implements NodeAggregateBasedTransformationInterface {
            public function __construct(
                /**
                 * The new Node Name to use as a string
                 */
                private readonly string $newNodeName,
                private readonly NodeAggregateCommandHandler $nodeAggregateCommandHandler
            ) {
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
        };
    }
}
