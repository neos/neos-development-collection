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
use Neos\Flow\Annotations as Flow;

/**
 * The interface to be implemented by content subgraphs
 */
interface ContentSubgraphInterface
{
    public function traverse(Domain\Model\NodeInterface $startNode, Domain\ValueObject\NodeTypeConstraints $nodeTypeConstraints, callable $callback, Domain\Service\Context $contentContext = null);

    /**
     * @param Domain\ValueObject\NodeIdentifier $nodeIdentifier
     * @param Domain\Service\Context|null $contentContext
     * @return Domain\Model\NodeInterface|null
     */
    public function findNodeByIdentifier(Domain\ValueObject\NodeIdentifier $nodeIdentifier, Domain\Service\Context $contentContext = null);

    /**
     * @param Domain\ValueObject\NodeIdentifier $parentIdentifier
     * @param Domain\ValueObject\NodeTypeConstraints $nodeTypeConstraints
     * @param int|null $limit
     * @param int|null $offset
     * @param Domain\Service\Context|null $contentContext
     * @return array
     */
    public function findNodesByParent(Domain\ValueObject\NodeIdentifier $parentIdentifier, Domain\ValueObject\NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null, Domain\Service\Context $contentContext = null): array;

    public function countChildNodes(Domain\ValueObject\NodeIdentifier $parentIdentifier, Domain\ValueObject\NodeTypeConstraints $nodeTypeConstraints = null): int;

    /**
     * @param Domain\ValueObject\NodeIdentifier $childIdentifier
     * @param Domain\Service\Context|null $contentContext
     * @return Domain\Model\NodeInterface|null
     */
    public function findParentNode(Domain\ValueObject\NodeIdentifier $childIdentifier, Domain\Service\Context $contentContext = null);

    /**
     * @param Domain\ValueObject\NodeIdentifier $parentIdentifier
     * @param Domain\Service\Context|null $contentContext
     * @return Domain\Model\NodeInterface|null
     */
    public function findFirstChild(Domain\ValueObject\NodeIdentifier $parentIdentifier, Domain\Service\Context $contentContext = null);

    /**
     * @param string $path
     * @return Domain\Model\NodeInterface|null
     */
    public function findByPath(string $path);

    /**
     * @param Domain\ValueObject\NodeIdentifier $parentIdentifier
     * @param string $edgeName
     * @param Domain\Service\Context|null $contentContext
     * @return Domain\Model\NodeInterface|null
     */
    public function findNodeByParentAlongPath(Domain\ValueObject\NodeIdentifier $parentIdentifier, string $edgeName, Domain\Service\Context $contentContext = null);

    /**
     * @param string $nodeTypeName
     * @param Domain\Service\Context|null $contentContext
     * @return array|Domain\Model\NodeInterface[]
     */
    public function findNodesByType(string $nodeTypeName, Domain\Service\Context $contentContext = null): array;

    public function findRootNode(): Domain\Model\NodeInterface;

    public function getIdentifier(): string;

    public function getDimensionValues(): Domain\ValueObject\DimensionValueCombination;

    public function getContentStreamIdentifier(): Domain\ValueObject\ContentStreamIdentifier;
}
