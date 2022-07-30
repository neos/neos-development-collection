<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Service\View;

use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\ContentRepository\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Projection\NodeHiddenState\NodeHiddenStateProjector;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\SharedModel\NodeAddressFactory;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeConstraintParser;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Log\Utility\LogEnvironment;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\Security\Authorization\Privilege\NodeTreePrivilege;
use Neos\Utility\Arrays;
use Neos\ContentRepository\Security\Authorization\Privilege\Node\NodePrivilegeSubject;
use Psr\Log\LoggerInterface;

/**
 * An View specialized on single or multiple Nodes in a tree structure
 *
 * NOTE: This class only exists for backwards compatibility with not-yet refactored service end points and service
 *       controllers.
 *
 * @Flow\Scope("prototype")
 */
class NodeView extends JsonView
{
    /**
     * @var integer
     */
    public const STYLE_LIST = 1;
    public const STYLE_TREE = 2;

    /**
     * @var integer
     */
    protected $outputStyle;

    /**
     * @Flow\Inject
     * @var LoggerInterface
     */
    protected $systemLogger;

    /**
     * @Flow\Inject
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    #[Flow\Inject]
    protected NodeAccessorManager $nodeAccessorManager;

    /**
     * Assigns a node to the NodeView.
     *
     * @param NodeInterface $node The node to render
     * @param array<int,string> $propertyNames Optional list of property names to include in the JSON output
     * @return void
     */
    public function assignNode(
        NodeInterface $node,
        array $propertyNames = ['name', 'path', 'identifier', 'properties', 'nodeType']
    ) {
        $this->setConfiguration(
            [
                'value' => [
                    'data' => [
                        '_only' => ['name', 'path', 'identifier', 'properties', 'nodeType'],
                        '_descend' => ['properties' => $propertyNames]
                    ]
                ]
            ]
        );
        $this->assign('value', ['data' => $node, 'success' => true]);
    }

    /**
     * @throws \Neos\Eel\Exception
     */
    public function assignNodes(Nodes $nodes): void
    {
        $data = [];
        foreach ($nodes as $node) {
            if (!$node->getClassification()->isRoot()) {
                $closestDocumentNode = $this->findClosestDocumentNode($node);
                if ($closestDocumentNode !== null) {
                    $contentRepository = $this->contentRepositoryRegistry->get($node->getSubgraphIdentity()->contentRepositoryIdentifier);
                    $nodeAddressFactory = NodeAddressFactory::create($contentRepository);
                    $data[] = [
                        'nodeContextPath' => $nodeAddressFactory->createFromNode($node)->serializeForUri(),
                        'documentNodeContextPath' => $nodeAddressFactory->createFromNode($closestDocumentNode)
                            ->serializeForUri(),
                    ];
                } else {
                    $this->systemLogger->info(sprintf(
                        'You have a node that is no longer connected to a document node ancestor.'
                            . ' Name: %s (Identifier: %s)',
                        $node->getNodeName(),
                        $node->getNodeAggregateIdentifier()
                    ), LogEnvironment::fromMethodName(__METHOD__));
                }
            }
        }

        $this->assign('value', ['data' => $data, 'success' => true]);
    }

    private function findClosestDocumentNode(NodeInterface $node): ?NodeInterface
    {
        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            $node->getSubgraphIdentity()
        );

        $documentNode = $node;
        while ($documentNode instanceof NodeInterface) {
            if ($documentNode->getNodeType()->isOfType(NodeTypeNameFactory::NAME_DOCUMENT)) {
                return $documentNode;
            }
            $documentNode = $nodeAccessor->findParentNode($documentNode);
        }

        return null;
    }

    /**
     * Prepares this view to render a list or tree of child nodes of the given node.
     *
     * @param NodeInterface $node The node to fetch child nodes of
     * @param string $nodeTypeFilter Criteria for filtering the child nodes
     * @param integer $outputStyle Either STYLE_TREE or STYLE_list
     * @param integer $depth How many levels of childNodes (0 = unlimited)
     * @param NodeInterface $untilNode if given, expand all nodes on the rootline towards $untilNode,
     *                                 no matter what is defined with $depth.
     * @return void
     */
    public function assignChildNodes(
        NodeInterface $node,
        $nodeTypeFilter,
        $outputStyle = self::STYLE_LIST,
        $depth = 0,
        NodeInterface $untilNode = null
    ) {
        $this->outputStyle = $outputStyle;
        $nodes = [];
        if (
            $this->privilegeManager->isGranted(
                NodeTreePrivilege::class,
                new NodePrivilegeSubject($node)
            )
        ) {
            $this->collectChildNodeData(
                $nodes,
                $node,
                ($nodeTypeFilter === '' ? null : $nodeTypeFilter),
                $depth,
                $untilNode
            );
        }
        $this->setConfiguration(['value' => ['data' => ['_descendAll' => []]]]);

        $this->assign('value', ['data' => $nodes, 'success' => true]);
    }

    /**
     * Prepares this view to render a list or tree of given node including child nodes.
     *
     * @param NodeInterface $node The node to fetch child nodes of
     * @param string $nodeTypeFilter Criteria for filtering the child nodes
     * @param integer $depth How many levels of childNodes (0 = unlimited)
     * @param NodeInterface $untilNode if given, expand all nodes on the rootline towards $untilNode,
     *                                 no matter what is defined with $depth.
     * @return void
     */
    public function assignNodeAndChildNodes(
        NodeInterface $node,
        $nodeTypeFilter = '',
        $depth = 0,
        NodeInterface $untilNode = null
    ) {
        $this->outputStyle = self::STYLE_TREE;
        $data = [];
        if (
            $this->privilegeManager->isGranted(
                NodeTreePrivilege::class,
                new NodePrivilegeSubject($node)
            )
        ) {
            $childNodes = [];
            $this->collectChildNodeData(
                $childNodes,
                $node,
                ($nodeTypeFilter === '' ? null : $nodeTypeFilter),
                $depth,
                $untilNode
            );
            $data = $this->collectTreeNodeData($node, true, $childNodes, $childNodes !== []);
        }
        $this->setConfiguration(['value' => ['data' => ['_descendAll' => []]]]);

        $this->assign('value', ['data' => $data, 'success' => true]);
    }

    /**
     * Prepares this view to render a list or tree of filtered nodes.
     *
     * @param NodeInterface $node
     * @param Nodes $matchedNodes
     * @param int $outputStyle Either STYLE_TREE or STYLE_list
     * @return void
     */
    public function assignFilteredChildNodes(NodeInterface $node, Nodes $matchedNodes, $outputStyle = self::STYLE_LIST)
    {
        $this->outputStyle = $outputStyle;
        $nodes = $this->collectParentNodeData($node, $matchedNodes);
        $this->setConfiguration(['value' => ['data' => ['_descendAll' => []]]]);

        $this->assign('value', ['data' => $nodes, 'success' => true]);
    }

    /**
     * Collect node data and traverse child nodes
     *
     * @param array<mixed> &$nodes
     * @param NodeInterface $node
     * @param ?string $nodeTypeFilter
     * @param integer $depth levels of child nodes to fetch. 0 = unlimited
     * @param NodeInterface $untilNode if given, expand all nodes on the rootline towards $untilNode,
     *                                 no matter what is defined with $depth.
     * @param integer $recursionPointer current recursion level
     * @return void
     */
    protected function collectChildNodeData(
        array &$nodes,
        NodeInterface $node,
        $nodeTypeFilter,
        $depth = 0,
        NodeInterface $untilNode = null,
        $recursionPointer = 1
    ) {
        $nodeAccessor = $this->nodeAccessorManager->accessorFor(
            $node->getSubgraphIdentity()
        );
        $contentRepository = $this->contentRepositoryRegistry->get($node->getSubgraphIdentity()->contentRepositoryIdentifier);
        $nodeTypeConstraintParser = NodeTypeConstraintParser::create($contentRepository);

        $nodeTypeConstraints = $nodeTypeFilter
            ? $nodeTypeConstraintParser->parseFilterString($nodeTypeFilter)
            : null;
        foreach ($nodeAccessor->findChildNodes($node, $nodeTypeConstraints) as $childNode) {
            if (!$this->privilegeManager->isGranted(NodeTreePrivilege::class, new NodePrivilegeSubject($childNode))) {
                continue;
            }
            $expand = ($depth === 0 || $recursionPointer < $depth);

            /** @todo traverse up in this case to avoid path checks */
            if (
                $expand === false
                && $untilNode !== null
                && strpos(
                    (string)$nodeAccessor->findNodePath($untilNode),
                    (string)$nodeAccessor->findNodePath($childNode),
                ) === 0
                && $childNode !== $untilNode
            ) {
                // in case $untilNode is set, and the current childNode is on the rootline of $untilNode
                // (and not the node itself), expand the node.
                $expand = true;
            }

            switch ($this->outputStyle) {
                case self::STYLE_LIST:
                    $contentRepository = $this->contentRepositoryRegistry->get($childNode->getSubgraphIdentity()->contentRepositoryIdentifier);
                    $nodeAddressFactory = NodeAddressFactory::create($contentRepository);
                    $childNodeAddress = $nodeAddressFactory->createFromNode($childNode);
                    $properties = $childNode->getProperties();
                    $properties['__contextNodePath'] = $childNodeAddress->serializeForUri();
                    $properties['__workspaceName'] = $childNodeAddress->workspaceName;
                    $properties['__nodeName'] = $childNode->getNodeName();
                    $properties['__nodeType'] = $childNode->getNodeTypeName();
                    $properties['__title'] = $childNode->getNodeTypeName()->equals(NodeTypeNameFactory::forDocument())
                        ? $childNode->getProperty('title')
                        : $childNode->getLabel();
                    array_push($nodes, $properties);
                    if ($expand) {
                        $this->collectChildNodeData(
                            $nodes,
                            $childNode,
                            $nodeTypeFilter,
                            $depth,
                            $untilNode,
                            ($recursionPointer + 1)
                        );
                    }
                    break;
                case self::STYLE_TREE:
                    $children = [];
                    $grandChildNodes = $nodeAccessor->findChildNodes($childNode, $nodeTypeConstraints);
                    $hasChildNodes = $grandChildNodes->count() > 0;
                    if ($expand && $hasChildNodes) {
                        $this->collectChildNodeData(
                            $children,
                            $childNode,
                            $nodeTypeFilter,
                            $depth,
                            $untilNode,
                            ($recursionPointer + 1)
                        );
                    }
                    array_push($nodes, $this->collectTreeNodeData($childNode, $expand, $children, $hasChildNodes));
            }
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function collectParentNodeData(NodeInterface $rootNode, Nodes $nodes): array
    {
        $rootNodeAccessor = $this->nodeAccessorManager->accessorFor(
            $rootNode->getSubgraphIdentity()
        );
        $rootNodePath = (string)$rootNodeAccessor->findNodePath($rootNode);
        $nodeCollection = [];

        $addNode = function (NodeInterface $node, bool $matched) use ($rootNodePath, &$nodeCollection) {
            $nodeAccessor = $this->nodeAccessorManager->accessorFor(
                $node->getSubgraphIdentity()
            );
            $nodePath = (string)$nodeAccessor->findNodePath($node);
            $path = str_replace('/', '.children.', substr($nodePath, strlen($rootNodePath) + 1));
            if ($path !== '') {
                $nodeCollection = Arrays::setValueByPath($nodeCollection, $path . '.node', $node);
                if ($matched === true) {
                    $nodeCollection = Arrays::setValueByPath($nodeCollection, $path . '.matched', true);
                }
            }
        };

        $findParent = function (NodeInterface $node) use (&$findParent, &$addNode) {
            $nodeAccessor = $this->nodeAccessorManager->accessorFor(
                $node->getSubgraphIdentity()
            );
            $parent = $nodeAccessor->findParentNode($node);
            if ($parent !== null) {
                if ($this->privilegeManager->isGranted(NodeTreePrivilege::class, new NodePrivilegeSubject($parent))) {
                    $addNode($parent, false);
                    $findParent($parent);
                }
            }
        };

        foreach ($nodes as $node) {
            if ($this->privilegeManager->isGranted(NodeTreePrivilege::class, new NodePrivilegeSubject($node))) {
                $addNode($node, true);
                $findParent($node);
            }
        }

        $treeNodes = [];
        $self = $this;
        $collectTreeNodeData = function (array &$treeNodes, array $node) use (&$collectTreeNodeData, $self) {
            $children = [];
            if (isset($node['children'])) {
                foreach ($node['children'] as $childNode) {
                    $collectTreeNodeData($children, $childNode);
                }
            }
            $treeNodes[] = $self->collectTreeNodeData(
                $node['node'],
                true,
                $children,
                $children !== [],
                isset($node['matched'])
            );
        };

        /** @var iterable<mixed> $nodeCollection */
        foreach ($nodeCollection as $firstLevelNode) {
            $collectTreeNodeData($treeNodes, $firstLevelNode);
        }

        return $treeNodes;
    }

    /**
     * @param array<mixed> $children
     * @return array<string,mixed>
     */
    public function collectTreeNodeData(
        NodeInterface $node,
        bool $expand = true,
        array $children = [],
        bool $hasChildNodes = false,
        bool $matched = false
    ): array {
        $contentRepository = $this->contentRepositoryRegistry->get($node->getSubgraphIdentity()->contentRepositoryIdentifier);
        $nodeAddressFactory = NodeAddressFactory::create($contentRepository);
        $nodeAddress = $nodeAddressFactory->createFromNode($node);
        $nodeHiddenStateFinder = $contentRepository->projectionState(NodeHiddenStateProjector::class);
        $hiddenState = $nodeHiddenStateFinder->findHiddenState(
            $nodeAddress->contentStreamIdentifier,
            $nodeAddress->dimensionSpacePoint,
            $nodeAddress->nodeAggregateIdentifier
        );

        $classes = [];
        if ($hiddenState->isHidden() === true) {
            array_push($classes, 'neos-hidden');
        }
        if ($node->getProperty('hiddenInIndex') === true) {
            array_push($classes, 'neos-hiddenInIndex');
        }
        if ($matched) {
            array_push($classes, 'neos-matched');
        }

        $uriBuilder = $this->controllerContext->getUriBuilder();
        $nodeType = $node->getNodeType();
        $nodeTypeConfiguration = $nodeType->getFullConfiguration();
        if ($node->getNodeType()->isOfType('Neos.Neos:Document')) {
            $uriForNode = $uriBuilder->reset()->setFormat('html')->setCreateAbsoluteUri(true)->uriFor(
                'show',
                ['node' => $nodeAddress->serializeForUri()],
                'Frontend\Node',
                'Neos.Neos'
            );
        } else {
            $uriForNode = '#';
        }
        $label = $node->getLabel();
        $nodeTypeLabel = $node->getNodeType()->getLabel();
        $treeNode = [
            'key' => $nodeAddress->serializeForUri(),
            'title' => $label,
            'fullTitle' => $node->getProperty('title'),
            'nodeTypeLabel' => $nodeTypeLabel,
            'tooltip' => '', // will be filled on the client side, because nodeTypeLabel contains
                             // the localization string instead of the localized value
            'href' => $uriForNode,
            'isFolder' => $hasChildNodes,
            'isLazy' => ($hasChildNodes && !$expand),
            'nodeType' => $nodeType->getName(),
            'isAutoCreated' => $node->getClassification() === NodeAggregateClassification::CLASSIFICATION_TETHERED,
            'expand' => $expand,
            'addClass' => implode(' ', $classes),
            'name' => $node->getNodeName(),
            'iconClass' => isset($nodeTypeConfiguration['ui']) && isset($nodeTypeConfiguration['ui']['icon'])
                ? $nodeTypeConfiguration['ui']['icon']
                : '',
            'isHidden' => $hiddenState->isHidden()
        ];
        if ($hasChildNodes) {
            $treeNode['children'] = $children;
        }
        return $treeNode;
    }
}
