<?php
namespace Neos\EventSourcedNeosAdjustments\Ui\ContentRepository\Service;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\Parameters\ContextParameters;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\TraversableNode;
use Neos\EventSourcedNeosAdjustments\Domain\Context\Content\NodeAddressFactory;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Aop\JoinPointInterface;

/**
 * @Flow\Aspect
 * @Flow\Scope("singleton")
 */
class NodeServiceAspect
{

    /**
     * @Flow\Inject
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * @Flow\Around("method(Neos\Neos\Ui\ContentRepository\Service\NodeService->getNodeFromContextPath())")
     * @param JoinPointInterface $joinPoint the join point
     * @return mixed
     */
    public function getNodeFromContextPath(JoinPointInterface $joinPoint)
    {
        $contextPath = $joinPoint->getMethodArgument('contextPath');

        $nodeAddress = $this->nodeAddressFactory->createFromUriString($contextPath);
        $subgraph = $this->contentGraph
            ->getSubgraphByIdentifier($nodeAddress->getContentStreamIdentifier(), $nodeAddress->getDimensionSpacePoint());
        $node = $subgraph->findNodeByNodeAggregateIdentifier($nodeAddress->getNodeAggregateIdentifier());
        // TODO: Context Parameter Handling
        return new TraversableNode($node, $subgraph, new ContextParameters(new \DateTimeImmutable(), [], true, false));
    }

}
