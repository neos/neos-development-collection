<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Ui\Fusion\Helper;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\NodeType\NodeTypeConstraintFactory;
use Neos\ContentRepository\Domain\Model\NodeType;
use Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface;
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\NodeHiddenState\NodeHiddenStateFinder;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedNeosAdjustments\Ui\Service\Mapping\NodePropertyConverterService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Domain\Service\ContentContext;
use Neos\Neos\Service\LinkingService;
use Neos\Neos\TypeConverter\EntityToIdentityConverter;
use Neos\Neos\Ui\Domain\Service\UserLocaleService;
use Neos\Neos\Ui\Service\NodePolicyService;

/**
 * @Flow\Scope("singleton")
 */
class NodeInfoHelper implements ProtectedContextAwareInterface
{
    /**
     * @Flow\Inject
     * @var NodePolicyService
     */
    protected $nodePolicyService;

    /**
     * @Flow\Inject
     * @var UserLocaleService
     */
    protected $userLocaleService;

    /**
     * @Flow\Inject
     * @var LinkingService
     */
    protected $linkingService;

    /**
     * @Flow\Inject
     * @var NodeTypeConstraintFactory
     */
    protected $nodeTypeConstraintFactory;

    /**
     * @Flow\Inject
     * @var \Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     * @Flow\Inject
     * @var EntityToIdentityConverter
     */
    protected $entityToIdentityConverter;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var NodePropertyConverterService
     */
    protected $nodePropertyConverterService;

    /**
     * @Flow\Inject
     * @var NodeHiddenStateFinder
     */
    protected $nodeHiddenStateFinder;

    /**
     * @Flow\InjectConfiguration(path="userInterface.navigateComponent.nodeTree.presets.default.baseNodeType", package="Neos.Neos")
     * @var string
     */
    protected $baseNodeType;

    /**
     * @Flow\InjectConfiguration(path="userInterface.navigateComponent.nodeTree.loadingDepth", package="Neos.Neos")
     * @var string
     */
    protected $loadingDepth;

    /**
     * @Flow\InjectConfiguration(path="nodeTypeRoles.document", package="Neos.Neos.Ui")
     * @var string
     */
    protected $documentNodeTypeRole;

    /**
     * @Flow\InjectConfiguration(path="nodeTypeRoles.ignored", package="Neos.Neos.Ui")
     * @var string
     */
    protected $ignoredNodeTypeRole;

    /**
     * @param TraversableNodeInterface $node
     * @param ControllerContext $controllerContext
     * @param bool $omitMostPropertiesForTreeState
     * @param string $nodeTypeFilterOverride
     * @return array
     * @deprecated See methods with specific names for different behaviors
     */
    public function renderNode(TraversableNodeInterface $node, ControllerContext $controllerContext = null, $omitMostPropertiesForTreeState = false, $nodeTypeFilterOverride = null)
    {
        return ($omitMostPropertiesForTreeState ?
            $this->renderNodeWithMinimalPropertiesAndChildrenInformation($node, $controllerContext, $nodeTypeFilterOverride) :
            $this->renderNodeWithPropertiesAndChildrenInformation($node, $controllerContext, $nodeTypeFilterOverride)
        );
    }

    /**
     * @param TraversableNodeInterface $node
     * @param ControllerContext|null $controllerContext
     * @param string $nodeTypeFilterOverride
     * @return array|null
     */
    public function renderNodeWithMinimalPropertiesAndChildrenInformation(TraversableNodeInterface $node, ControllerContext $controllerContext = null, string $nodeTypeFilterOverride = null)
    {
        //if (!$this->nodePolicyService->isNodeTreePrivilegeGranted($node)) {
        //    return null;
        //}
        $this->userLocaleService->switchToUILocale();

        $nodeInfo = $this->getBasicNodeInformation($node);
        $nodeInfo['properties'] = [
            // if we are only rendering the tree state, ensure _isHidden is sent to hidden nodes are correctly shown in the tree.
            '_hidden' => $this->nodeHiddenStateFinder->findHiddenState($node->getContentStreamIdentifier(), $node->getDimensionSpacePoint(), $node->getNodeAggregateIdentifier())->isHidden(),
            '_hiddenInIndex' => $node->getProperty('_hiddenInIndex'),
            //'_hiddenBeforeDateTime' => $node->getHiddenBeforeDateTime() instanceof \DateTimeInterface,
            //'_hiddenAfterDateTime' => $node->getHiddenAfterDateTime() instanceof \DateTimeInterface,
        ];

        if ($controllerContext !== null) {
            $nodeInfo = array_merge($nodeInfo, $this->getUriInformation($node, $controllerContext));
        }

        $baseNodeType = $nodeTypeFilterOverride ? $nodeTypeFilterOverride : $this->baseNodeType;
        $nodeTypeFilter = $this->buildNodeTypeFilterString($this->nodeTypeStringsToList($baseNodeType), $this->nodeTypeStringsToList($this->ignoredNodeTypeRole));

        $nodeInfo['children'] = $this->renderChildrenInformation($node, $nodeTypeFilter);

        $this->userLocaleService->switchToUILocale(true);

        return $nodeInfo;
    }

    /**
     * @param TraversableNodeInterface $node
     * @param ControllerContext|null $controllerContext
     * @param string $nodeTypeFilterOverride
     * @return array|null
     */
    public function renderNodeWithPropertiesAndChildrenInformation(TraversableNodeInterface $node, ControllerContext $controllerContext = null, string $nodeTypeFilterOverride = null)
    {
        //if (!$this->nodePolicyService->isNodeTreePrivilegeGranted($node)) {
        //    return null;
        //}

        $this->userLocaleService->switchToUILocale();

        $nodeInfo = $this->getBasicNodeInformation($node);
        $nodeInfo['properties'] = $this->nodePropertyConverterService->getPropertiesArray($node);
        $nodeInfo['isFullyLoaded'] = true;

        if ($controllerContext !== null) {
            $nodeInfo = array_merge($nodeInfo, $this->getUriInformation($node, $controllerContext));
        }

        $baseNodeType = $nodeTypeFilterOverride ? $nodeTypeFilterOverride : $this->baseNodeType;
        $nodeInfo['children'] = $this->renderChildrenInformation($node, $baseNodeType);

        $this->userLocaleService->switchToUILocale(true);

        return $nodeInfo;
    }

    /**
     * Get the "uri" and "previewUri" for the given node
     *
     * @param TraversableNodeInterface $node
     * @param ControllerContext $controllerContext
     * @return array
     */
    protected function getUriInformation(TraversableNodeInterface $node, ControllerContext $controllerContext): array
    {
        $nodeInfo = [];
        if (!$node->getNodeType()->isOfType($this->documentNodeTypeRole)) {
            return $nodeInfo;
        }
        $nodeInfo['uri'] = $this->previewUri($node, $controllerContext);
        return $nodeInfo;
    }

    /**
     * Get the basic information about a node.
     *
     * @param TraversableNodeInterface $node
     * @return array
     */
    protected function getBasicNodeInformation(TraversableNodeInterface $node): array
    {
        return [
            'contextPath' => $this->nodeAddressFactory->createFromTraversableNode($node)->serializeForUri(),
            'name' => $node->getNodeName() ? $node->getNodeName()->jsonSerialize() : null,
            'identifier' => $node->getNodeAggregateIdentifier()->jsonSerialize(),
            'nodeType' => $node->getNodeType()->getName(),
            'label' => $node->getLabel(),
            'isAutoCreated' => self::isAutoCreated($node),
            'depth' => $node->findNodePath()->getDepth(),
            'children' => [],
            'parent' => $this->nodeAddressFactory->createFromTraversableNode($node->findParentNode())->serializeForUri(),
            'matchesCurrentDimensions' => $node->getDimensionSpacePoint()->equals($node->getOriginDimensionSpacePoint())
        ];
    }

    private static function isAutoCreated(TraversableNodeInterface $node)
    {
        $parent = $node->findParentNode();
        if ($parent) {
            if (array_key_exists((string)$node->getNodeName(), $parent->getNodeType()->getAutoCreatedChildNodes())) {
                return true;
            }
        }
        return false;
    }

    public static function isNodeTypeAllowedAsChildNode(TraversableNodeInterface $node, NodeType $nodeType)
    {
        if (self::isAutoCreated($node)) {
            return $node->findParentNode()->getNodeType()->allowsGrandchildNodeType((string)$node->getNodeName(), $nodeType);
        } else {
            return $node->getNodeType()->allowsChildNodeType($nodeType);
        }
    }


    /**
     * Get information for all children of the given parent node.
     *
     * @param TraversableNodeInterface $node
     * @param string $nodeTypeFilterString
     * @return array
     * @throws \Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\Exception\NodeAddressCannotBeSerializedException
     */
    protected function renderChildrenInformation(TraversableNodeInterface $node, string $nodeTypeFilterString): array
    {
        $documentChildNodes = $node->findChildNodes($this->nodeTypeConstraintFactory->parseFilterString($nodeTypeFilterString));
        // child nodes for content tree, must not include those nodes filtered out by `baseNodeType`
        $contentChildNodes = $node->findChildNodes($this->nodeTypeConstraintFactory->parseFilterString($this->buildContentChildNodeFilterString()));
        $childNodes = $documentChildNodes->merge($contentChildNodes);

        $infos = [];
        foreach ($childNodes as $childNode) {
            $infos[] = [
                'contextPath' => $this->nodeAddressFactory->createFromTraversableNode($childNode)->serializeForUri(),
                'nodeType' => $childNode->getNodeType()->getName() // TODO: DUPLICATED; should NOT be needed!!!
            ];
        };
        return $infos;
    }

    /**
     * @param array $nodes
     * @param ControllerContext $controllerContext
     * @param bool $omitMostPropertiesForTreeState
     * @return array
     */
    public function renderNodes(array $nodes, ControllerContext $controllerContext, $omitMostPropertiesForTreeState = false): array
    {
        $methodName = $omitMostPropertiesForTreeState ? 'renderNodeWithMinimalPropertiesAndChildrenInformation' : 'renderNodeWithPropertiesAndChildrenInformation';
        $mapper = function (TraversableNodeInterface $node) use ($controllerContext, $methodName) {
            return $this->$methodName($node, $controllerContext);
        };

        return array_values(array_filter(array_map($mapper, $nodes)));
    }

    /**
     * @param array $nodes
     * @param ControllerContext $controllerContext
     * @return array
     */
    public function renderNodesWithParents(array $nodes, ControllerContext $controllerContext): array
    {
        // For search operation we want to include all nodes, not respecting the "baseNodeType" setting
        $baseNodeTypeOverride = $this->documentNodeTypeRole;
        $renderedNodes = [];

        /** @var TraversableNodeInterface $node */
        foreach ($nodes as $node) {
            if (array_key_exists($node->getPath(), $renderedNodes)) {
                $renderedNodes[$node->getPath()]['matched'] = true;
            } elseif ($renderedNode = $this->renderNodeWithMinimalPropertiesAndChildrenInformation($node, $controllerContext, $baseNodeTypeOverride)) {
                $renderedNode['matched'] = true;
                $renderedNodes[$node->getPath()] = $renderedNode;
            } else {
                continue;
            }

            /* @var $contentContext ContentContext */
            $contentContext = $node->getContext();
            $siteNodePath = $contentContext->getCurrentSiteNode()->getPath();
            $parentNode = $node->getParent();
            if ($parentNode === null) {
                // There are a multitude of reasons why a node might not have a parent and we should ignore these gracefully.
                continue;
            }

            // we additionally need to check that our parent nodes are underneath the site node; otherwise it might happen that
            // we try to send the "/sites" node to the UI (which we cannot do, because this does not have an URL)
            $parentNodeIsUnderneathSiteNode = (strpos($parentNode->getPath(), $siteNodePath) === 0);
            while ($parentNode->getNodeType()->isOfType($baseNodeTypeOverride) && $parentNodeIsUnderneathSiteNode) {
                if (array_key_exists($parentNode->getPath(), $renderedNodes)) {
                    $renderedNodes[$parentNode->getPath()]['intermediate'] = true;
                } else {
                    $renderedParentNode = $this->renderNodeWithMinimalPropertiesAndChildrenInformation($parentNode, $controllerContext, $baseNodeTypeOverride);
                    if ($renderedParentNode) {
                        $renderedParentNode['intermediate'] = true;
                        $renderedNodes[$parentNode->getPath()] = $renderedParentNode;
                    }
                }
                $parentNode = $parentNode->getParent();
                if ($parentNode === null) {
                    // There are a multitude of reasons why a node might not have a parent and we should ignore these gracefully.
                    break;
                }
            }
        }

        return array_values($renderedNodes);
    }

    /**
     * @param TraversableNodeInterface $documentNode
     * @param ControllerContext $controllerContext
     * @return array
     */
    public function renderDocumentNodeAndChildContent(TraversableNodeInterface $documentNode, ControllerContext $controllerContext)
    {
        return $this->renderNodeAndChildContent($documentNode, $controllerContext);
    }

    /**
     * @param TraversableNodeInterface $node
     * @param ControllerContext $controllerContext
     * @return array
     */
    protected function renderNodeAndChildContent(TraversableNodeInterface $node, ControllerContext $controllerContext)
    {
        $reducer = function ($nodes, $node) use ($controllerContext) {
            $nodes = array_merge($nodes, $this->renderNodeAndChildContent($node, $controllerContext));

            return $nodes;
        };

        return array_reduce($node->getChildNodes($this->buildContentChildNodeFilterString()), $reducer, [$node->getContextPath() => $this->renderNodeWithPropertiesAndChildrenInformation($node, $controllerContext)]);
    }

    /**
     * @param TraversableNodeInterface $site
     * @param TraversableNodeInterface $documentNode
     * @param ControllerContext $controllerContext
     * @return array
     */
    public function defaultNodesForBackend(TraversableNodeInterface $site, TraversableNodeInterface $documentNode, ControllerContext $controllerContext): array
    {
        return [
            ($this->nodeAddressFactory->createFromTraversableNode($site)->serializeForUri()) => $this->renderNodeWithPropertiesAndChildrenInformation($site, $controllerContext),
            ($this->nodeAddressFactory->createFromTraversableNode($documentNode)->serializeForUri()) => $this->renderNodeWithPropertiesAndChildrenInformation($documentNode, $controllerContext)
        ];
    }

    /**
     * @param NodeAddress $nodeAddress
     * @param ControllerContext $controllerContext
     * @return string
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     */
    public function uri(NodeAddress $nodeAddress, ControllerContext $controllerContext)
    {
        $request = $controllerContext->getRequest()->getMainRequest();
        $uriBuilder = clone $controllerContext->getUriBuilder();
        $uriBuilder->setRequest($request);
        $uri = $uriBuilder
            ->reset()
            ->setFormat('html')
            ->setCreateAbsoluteUri(true)
            ->uriFor('show', ['node' => $nodeAddress], 'Frontend\Node', 'Neos.Neos');
        return $uri;
    }

    /**
     * @param TraversableNodeInterface $node
     * @param ControllerContext $controllerContext
     * @return string
     * @throws \Neos\Flow\Mvc\Routing\Exception\MissingActionNameException
     */
    public function previewUri(TraversableNodeInterface $node, ControllerContext $controllerContext)
    {
        $nodeAddress = $this->nodeAddressFactory->createFromTraversableNode($node);
        $request = $controllerContext->getRequest()->getMainRequest();
        $uriBuilder = clone $controllerContext->getUriBuilder();
        $uriBuilder->setRequest($request);
        $uri = $uriBuilder
            ->reset()
            ->setFormat('html')
            ->setCreateAbsoluteUri(true)
            ->uriFor('preview', ['node' => $nodeAddress->serializeForUri()], 'Frontend\Node', 'Neos.Neos');
        return $uri;
    }

    /**
     * @param string ...$nodeTypeStrings
     * @return string[]
     */
    protected function nodeTypeStringsToList(string ...$nodeTypeStrings)
    {
        $reducer = function ($nodeTypeList, $nodeTypeString) {
            $nodeTypeParts = explode(',', $nodeTypeString);
            foreach ($nodeTypeParts as $nodeTypeName) {
                $nodeTypeList[] = trim($nodeTypeName);
            }

            return $nodeTypeList;
        };

        return array_reduce($nodeTypeStrings, $reducer, []);
    }

    /**
     * @param array $includedNodeTypes
     * @param array $excludedNodeTypes
     * @return string
     */
    protected function buildNodeTypeFilterString(array $includedNodeTypes, array $excludedNodeTypes)
    {
        $preparedExcludedNodeTypes = array_map(function ($nodeTypeName) {
            return '!' . $nodeTypeName;
        }, $excludedNodeTypes);
        $mergedIncludesAndExcludes = array_merge($includedNodeTypes, $preparedExcludedNodeTypes);
        return implode(',', $mergedIncludesAndExcludes);
    }

    /**
     * @return string
     */
    protected function buildContentChildNodeFilterString()
    {
        return $this->buildNodeTypeFilterString([], $this->nodeTypeStringsToList($this->documentNodeTypeRole, $this->ignoredNodeTypeRole));
    }

    public function nodeAddress(TraversableNodeInterface $node): NodeAddress
    {
        return $this->nodeAddressFactory->createFromTraversableNode($node);
    }

    public function serializedNodeAddress(TraversableNodeInterface $node): string
    {
        return $this->nodeAddressFactory->createFromTraversableNode($node)->serializeForUri();
    }

    public function inBackend(TraversableNodeInterface $node)
    {
        return !$this->nodeAddressFactory->createFromTraversableNode($node)->isInLiveWorkspace();
    }

    /**
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
