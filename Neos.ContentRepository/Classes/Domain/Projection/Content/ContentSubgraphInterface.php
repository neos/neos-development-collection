<?php

namespace Neos\ContentRepository\Domain\Projection\Content;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\ContentRepository\Domain;
use Neos\ContentRepository\Domain\Service\Context;
use Neos\Flow\Annotations as Flow;

/**
 * The interface to be implemented by content subgraphs
 */
interface ContentSubgraphInterface
{
    public function traverse(Domain\Model\NodeInterface $startNode, Domain\ValueObject\NodeTypeConstraints $nodeTypeConstraints, callable $callback, Domain\Service\Context $contentContext = null);

    /**
     * @param Domain\ValueObject\NodeAggregateIdentifier $nodeIdentifier
     * @param Domain\Service\Context|null $contentContext
     * @return Domain\Model\NodeInterface|null
     */
    public function findNodeByIdentifier(Domain\ValueObject\NodeAggregateIdentifier $nodeIdentifier, Domain\Service\Context $contentContext = null);

    /**
     * @param Domain\ValueObject\NodeAggregateIdentifier $parentIdentifier
     * @param Domain\ValueObject\NodeTypeConstraints $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @param Domain\Service\Context|null $contentContext
     * @return array|Domain\Model\NodeInterface[]
     */
    public function findChildNodes(Domain\ValueObject\NodeAggregateIdentifier $parentIdentifier, Domain\ValueObject\NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null, Domain\Service\Context $contentContext = null): array;

    public function countChildNodes(Domain\ValueObject\NodeAggregateIdentifier $parentIdentifier, Domain\ValueObject\NodeTypeConstraints $nodeTypeConstraints = null, Domain\Service\Context $contentContext = null): int;

    /**
     * @param Domain\ValueObject\NodeAggregateIdentifier $childIdentifier
     * @param Domain\Service\Context|null $contentContext
     * @return Domain\Model\NodeInterface|null
     */
    public function findParentNode(Domain\ValueObject\NodeAggregateIdentifier $childIdentifier, Domain\Service\Context $contentContext = null);

    /**
     * @param Domain\ValueObject\NodeAggregateIdentifier $parentIdentifier
     * @param Domain\Service\Context|null $contentContext
     * @return Domain\Model\NodeInterface|null
     */
    public function findFirstChildNode(Domain\ValueObject\NodeAggregateIdentifier $parentIdentifier, Domain\Service\Context $contentContext = null);

    /**
     * @param string $path
     * @return Domain\Model\NodeInterface|null
     */
    public function findNodeByPath(string $path, Domain\Service\Context $contentContext = null);

    /**
     * @param Domain\ValueObject\NodeAggregateIdentifier $parentIdentifier
     * @param string $edgeName
     * @param Domain\Service\Context|null $contentContext
     * @return Domain\Model\NodeInterface|null
     */
    public function findChildNodeAlongPath(Domain\ValueObject\NodeAggregateIdentifier $parentIdentifier, string $edgeName, Domain\Service\Context $contentContext = null);

    /**
     * @param string $nodeTypeName
     * @param Domain\Service\Context|null $contentContext
     * @return array|Domain\Model\NodeInterface[]
     */
    public function findNodesByType(string $nodeTypeName, Domain\Service\Context $contentContext = null): array;

    public function findRootNode(Context $context = null): Domain\Model\NodeInterface;

    public function getIdentifier(): Domain\ValueObject\SubgraphIdentifier;
}
