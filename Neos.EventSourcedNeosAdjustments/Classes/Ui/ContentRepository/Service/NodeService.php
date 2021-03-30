<?php
declare(strict_types=1);
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

use Neos\ContentRepository\Intermediary\Domain\NodeBasedReadModelInterface;
use Neos\ContentRepository\Intermediary\Domain\ReadModelRepository;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
class NodeService
{
    /**
     * @Flow\Inject
     * @var \Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * @Flow\Inject
     * @var ReadModelRepository
     */
    protected $readModelRepository;

    /**
     * Helper method to retrieve the closest document for a node
     */
    public function getClosestDocument(NodeBasedReadModelInterface $node): NodeBasedReadModelInterface
    {
        if ($node->getNodeType()->isOfType('Neos.Neos:Document')) {
            return $node;
        }

        $flowQuery = new FlowQuery([$node]);

        return $flowQuery->closest('[instanceof Neos.Neos:Document]')->get(0);
    }

    /**
     * Helper method to check if a given node is a document node.
     *
     * @param  NodeBasedReadModelInterface $node The node to check
     * @return boolean             A boolean which indicates if the given node is a document node.
     */
    public function isDocument(NodeBasedReadModelInterface $node): bool
    {
        return ($this->getClosestDocument($node) === $node);
    }

    /**
     * Converts a given context path to a node object
     */
    public function getNodeFromContextPath(string $contextPath): NodeBasedReadModelInterface
    {
        $nodeAddress = $this->nodeAddressFactory->createFromUriString($contextPath);

        return $this->readModelRepository->findByNodeAddress($nodeAddress, VisibilityConstraints::withoutRestrictions());
    }
}
