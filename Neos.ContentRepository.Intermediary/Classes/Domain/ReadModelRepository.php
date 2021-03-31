<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\Domain;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class ReadModelRepository
{
    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * @Flow\Inject
     * @var ReadModelFactory
     */
    protected $readModelFactory;

    public function findByNodeAddress(NodeAddress $nodeAddress, VisibilityConstraints $visibilityConstraints): ?NodeBasedReadModelInterface
    {
        $subgraph = $this->contentGraph->getSubgraphByIdentifier(
            $nodeAddress->getContentStreamIdentifier(),
            $nodeAddress->getDimensionSpacePoint(),
            $visibilityConstraints
        );
        if (!$subgraph) {
            return null;
        }
        $node = $subgraph->findNodeByNodeAggregateIdentifier($nodeAddress->getNodeAggregateIdentifier());

        return $node ? $this->readModelFactory->createReadModel($node, $subgraph) : null;
    }
}
