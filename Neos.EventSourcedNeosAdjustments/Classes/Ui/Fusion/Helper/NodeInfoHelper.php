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
use Neos\Eel\ProtectedContextAwareInterface;
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorInterface;
use Neos\EventSourcedContentRepository\ContentAccess\NodeAccessorManager;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddress;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\NodeAddressFactory;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\NodeHiddenState\NodeHiddenStateFinder;
use Neos\EventSourcedNeosAdjustments\EventSourcedRouting\NodeUriBuilder;
use Neos\EventSourcedNeosAdjustments\Ui\Service\Mapping\NodePropertyConverterService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ControllerContext;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Domain\Service\ContentContext;
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
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     * @Flow\Inject
     * @var NodeAccessorManager
     */
    protected $nodeAccessorManager;

    /**
     * @Flow\Inject
     * @var NodeTypeConstraintFactory
     */
    protected $nodeTypeConstraintFactory;

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
     * @param NodeInterface $node
     * @param ControllerContext $controllerContext
     * @param bool $omitMostPropertiesForTreeState
     * @param string $nodeTypeFilterOverride
     * @return array
     * @deprecated See methods with specific names for different behaviors
     */
    public function renderNode(NodeInterface $node, ControllerContext $controllerContext = null, $omitMostPropertiesForTreeState = false, $nodeTypeFilterOverride = null)
    {
        return ($omitMostPropertiesForTreeState ?
            $this->renderNodeWithMinimalPropertiesAndChildrenInformation($node, $controllerContext, $nodeTypeFilterOverride) :
            $this->renderNodeWithPropertiesAndChildrenInformation($node, $controllerContext, $nodeTypeFilterOverride)
        );
    }

    /**
     * @param NodeInterface $node
     * @param ControllerContext|null $controllerContext
     * @param string $nodeTypeFilterOverride
     * @return array|null
     */
    public function renderNodeWithMinimalPropertiesAndChildrenInformation(NodeInterface $node, ControllerContext $controllerContext = null, string $nodeTypeFilterOverride = null)
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
     * @param NodeInterface $node
     * @param ControllerContext|null $controllerContext
     * @param string $nodeTypeFilterOverride
     * @return array|null
     */
    public function renderNodeWithPropertiesAndChildrenInformation(NodeInterface $node, ControllerContext $controllerContext = null, string $nodeTypeFilterOverride = null)
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
     * @param NodeInterface $node
     * @param ControllerContext $controllerContext
     * @return array
     */
    protected function getUriInformation(NodeInterface $node, ControllerContext $controllerContext): array
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
     * @param NodeInterface $node
     * @return array
     */
    protected function getBasicNodeInformation(NodeInterface $node): array
    {
        $nodeAccessor = $this->nodeAccessorManager->accessorFor($node->getContentStreamIdentifier(), $node->getDimensionSpacePoint(), VisibilityConstraints::withoutRestrictions());
        return [
            'contextPath' => $this->nodeAddressFactory->createFromNode($node)->serializeForUri(),
            'name' => $node->getNodeName() ? $node->getNodeName()->jsonSerialize() : null,
            'identifier' => $node->getNodeAggregateIdentifier()->jsonSerialize(),
            'nodeType' => $node->getNodeType()->getName(),
            'label' => $node->getLabel(),
            'isAutoCreated' => self::isAutoCreated($node, $nodeAccessor),
            // TODO: depth is expensive to calculate; maybe let's get rid of this?
            'depth' => $nodeAccessor->findNodePath($node)->getDepth(),
            'children' => [],
            'parent' => $this->nodeAddressFactory->createFromNode($nodeAccessor->findParentNode($node))->serializeForUri(),
            'matchesCurrentDimensions' => $node->getDimensionSpacePoint()->equals($node->getOriginDimensionSpacePoint())
        ];
    }

    public static function isAutoCreated(NodeInterface $node, NodeAccessorInterface $nodeAccessor)
    {
        $parent = $nodeAccessor->findParentNode($node);
        if ($parent) {
            if (array_key_exists((string)$node->getNodeName(), $parent->getNodeType()->getAutoCreatedChildNodes())) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get information for all children of the given parent node.
     *
     * @param NodeInterface $node
     * @param string $nodeTypeFilterString
     * @return array
     * @throws \Neos\EventSourcedContentRepository\Domain\Context\NodeAddress\Exception\NodeAddressCannotBeSerializedException
     */
    protected function renderChildrenInformation(NodeInterface $node, string $nodeTypeFilterString): array
    {
        $nodeAccessor = $this->nodeAccessorManager->accessorFor($node->getContentStreamIdentifier(), $node->getDimensionSpacePoint(), VisibilityConstraints::withoutRestrictions());

        $documentChildNodes = $nodeAccessor->findChildNodes($node, $this->nodeTypeConstraintFactory->parseFilterString($nodeTypeFilterString));
        // child nodes for content tree, must not include those nodes filtered out by `baseNodeType`
        $contentChildNodes = $nodeAccessor->findChildNodes($node, $this->nodeTypeConstraintFactory->parseFilterString($this->buildContentChildNodeFilterString()));
        $childNodes = $documentChildNodes->merge($contentChildNodes);

        $infos = [];
        foreach ($childNodes as $childNode) {
            $infos[] = [
                'contextPath' => $this->nodeAddressFactory->createFromNode($childNode)->serializeForUri(),
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
        $mapper = function (NodeInterface $node) use ($controllerContext, $methodName) {
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

        /** @var NodeInterface $node */
        foreach ($nodes as $node) {
            $nodeAccessor = $this->nodeAccessorManager->accessorFor($node->getContentStreamIdentifier(), $node->getDimensionSpacePoint(), VisibilityConstraints::withoutRestrictions());

            $nodePath = $nodeAccessor->findNodePath($node);
            if (array_key_exists($nodePath, $renderedNodes)) {
                $renderedNodes[(string)$nodePath]['matched'] = true;
            } elseif ($renderedNode = $this->renderNodeWithMinimalPropertiesAndChildrenInformation($node, $controllerContext, $baseNodeTypeOverride)) {
                $renderedNode['matched'] = true;
                $renderedNodes[(string)$nodePath] = $renderedNode;
            } else {
                continue;
            }

            /* @var $contentContext ContentContext */
            $contentContext = $node->getContext();
            // TODO: $node->getContext() not supported anymore
            $siteNodePath = $contentContext->getCurrentSiteNode()->getPath();
            $parentNode = $nodeAccessor->findParentNode($node);
            if ($parentNode === null) {
                // There are a multitude of reasons why a node might not have a parent and we should ignore these gracefully.
                continue;
            }

            // we additionally need to check that our parent nodes are underneath the site node; otherwise it might happen that
            // we try to send the "/sites" node to the UI (which we cannot do, because this does not have an URL)
            $parentNodePath = $nodeAccessor->findNodePath($parentNode);
            $parentNodeIsUnderneathSiteNode = (strpos((string)$parentNodePath, $siteNodePath) === 0);
            while ($parentNode->getNodeType()->isOfType($baseNodeTypeOverride) && $parentNodeIsUnderneathSiteNode) {
                if (array_key_exists((string)$parentNodePath, $renderedNodes)) {
                    $renderedNodes[(string)$parentNodePath]['intermediate'] = true;
                } else {
                    $renderedParentNode = $this->renderNodeWithMinimalPropertiesAndChildrenInformation($parentNode, $controllerContext, $baseNodeTypeOverride);
                    if ($renderedParentNode) {
                        $renderedParentNode['intermediate'] = true;
                        $renderedNodes[(string)$parentNodePath] = $renderedParentNode;
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
     * @param NodeInterface $documentNode
     * @param ControllerContext $controllerContext
     * @return array
     */
    public function renderDocumentNodeAndChildContent(NodeInterface $documentNode, ControllerContext $controllerContext)
    {
        return $this->renderNodeAndChildContent($documentNode, $controllerContext);
    }

    /**
     * @param NodeInterface $node
     * @param ControllerContext $controllerContext
     * @return array
     */
    protected function renderNodeAndChildContent(NodeInterface $node, ControllerContext $controllerContext)
    {
        $reducer = function ($nodes, $node) use ($controllerContext) {
            $nodes = array_merge($nodes, $this->renderNodeAndChildContent($node, $controllerContext));

            return $nodes;
        };

        return array_reduce($node->getChildNodes($this->buildContentChildNodeFilterString()), $reducer, [$this->nodeAddressFactory->createFromNode($node)->serializeForUri() => $this->renderNodeWithPropertiesAndChildrenInformation($node, $controllerContext)]);
    }

    /**
     * @param NodeInterface $site
     * @param NodeInterface $documentNode
     * @param ControllerContext $controllerContext
     * @return array
     */
    public function defaultNodesForBackend(NodeInterface $site, NodeInterface $documentNode, ControllerContext $controllerContext): array
    {
        return [
            ($this->nodeAddressFactory->createFromNode($site)->serializeForUri()) => $this->renderNodeWithPropertiesAndChildrenInformation($site, $controllerContext),
            ($this->nodeAddressFactory->createFromNode($documentNode)->serializeForUri()) => $this->renderNodeWithPropertiesAndChildrenInformation($documentNode, $controllerContext)
        ];
    }

    /**
     * @param NodeAddress $nodeAddress
     * @param ControllerContext $controllerContext
     * @return string
     */
    public function uri(NodeAddress $nodeAddress, ControllerContext $controllerContext)
    {
        return (string)NodeUriBuilder::fromRequest($controllerContext->getRequest())->uriFor($nodeAddress);
    }

    /**
     * @param NodeInterface $node
     * @param ControllerContext $controllerContext
     * @return string
     */
    public function previewUri(NodeInterface $node, ControllerContext $controllerContext)
    {
        $nodeAddress = $this->nodeAddressFactory->createFromNode($node);
        return (string)NodeUriBuilder::fromRequest($controllerContext->getRequest())->previewUriFor($nodeAddress);
    }

    public function redirectUri(NodeInterface $node, ControllerContext $controllerContext): string
    {
        $nodeAddress = $this->nodeAddressFactory->createFromNode($node);
        return $controllerContext->getUriBuilder()
            ->reset()
            ->setCreateAbsoluteUri(true)
            ->setFormat('html')
            ->uriFor('redirectTo', ['node' => $nodeAddress->serializeForUri()], 'Backend', 'Neos.Neos.Ui');
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

    public function nodeAddress(NodeInterface $node): NodeAddress
    {
        return $this->nodeAddressFactory->createFromNode($node);
    }

    public function serializedNodeAddress(NodeInterface $node): string
    {
        return $this->nodeAddressFactory->createFromNode($node)->serializeForUri();
    }

    public function inBackend(NodeInterface $node)
    {
        return !$this->nodeAddressFactory->createFromNode($node)->isInLiveWorkspace();
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
