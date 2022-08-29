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

use Neos\ContentRepository\Core\CommandHandler\CommandResult;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\NodeRenaming\Command\ChangeNodeAggregateName;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamIdentifier;

class RenameNodeAggregateTransformationFactory implements TransformationFactoryInterface
{
    /**
     * @param array<string,string> $settings
     */
    public function build(
        array $settings,
        ContentRepository $contentRepository
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface {
        $newNodeName = $settings['newNodeName'];

        return new class (
            $newNodeName,
            $contentRepository
        ) implements NodeAggregateBasedTransformationInterface {
            public function __construct(
                /**
                 * The new Node Name to use as a string
                 */
                private readonly string $newNodeName,
                private readonly ContentRepository $contentRepository
            ) {
            }

            public function execute(
                NodeAggregate $nodeAggregate,
                ContentStreamIdentifier $contentStreamForWriting
            ): CommandResult {
                return $this->contentRepository->handle(new ChangeNodeAggregateName(
                    $contentStreamForWriting,
                    $nodeAggregate->nodeAggregateIdentifier,
                    NodeName::fromString($this->newNodeName),
                    UserIdentifier::forSystemUser()
                ));
            }
        };
    }
}
