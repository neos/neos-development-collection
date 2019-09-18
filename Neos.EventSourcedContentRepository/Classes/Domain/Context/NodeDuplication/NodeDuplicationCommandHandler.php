<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeDuplication;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Exception\NodeConstraintException;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream;
use Neos\ContentRepository\DimensionSpace\DimensionSpace;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamRepository;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Command\CreateNodeAggregateWithNode;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeAggregatesTypeIsAmbiguous;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature\ConstraintChecks;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature\NodeCreation;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature\NodeDisabling;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature\NodeModification;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature\NodeMove;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature\NodeReferencing;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature\NodeRemoval;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature\NodeRenaming;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature\NodeRetyping;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Feature\NodeVariation;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Context\NodeDuplication\Command\CopyNodesRecursively;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\EventSourcedContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;

final class NodeDuplicationCommandHandler
{
    /**
     * @Flow\Inject
     * @var NodeAggregateCommandHandler
     */
    protected $nodeAggregateCommandHandler;

    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    public function handleCopyNodesRecursively(CopyNodesRecursively $command)
    {
        $subgraph = $this->contentGraph->getSubgraphByIdentifier(
            $command->getContentStreamIdentifier(),
            $command->getOriginDimensionSpacePoint(),
            VisibilityConstraints::withoutRestrictions()
        );

        $node = $subgraph->findNodeByNodeAggregateIdentifier($command->getNodeAggregateIdentifier());


        $this->nodeAggregateCommandHandler->handleCreateNodeAggregateWithNode(
            new CreateNodeAggregateWithNode(

            )
        )
    }
}
