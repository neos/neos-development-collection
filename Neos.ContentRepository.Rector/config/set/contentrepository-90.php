<?php
declare (strict_types=1);

use Neos\ContentRepository\Rector\Rules\InjectContentRepositoryRegistryIfNeededRector;
use Neos\ContentRepository\Rector\Rules\NodeGetChildNodesRector;
use Neos\ContentRepository\Projection\ContentGraph\Node;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\MethodCall\RenameMethodRector;
use Rector\Renaming\Rector\Name\RenameClassRector;
use Rector\Renaming\ValueObject\MethodCallRename;
use Rector\Transform\Rector\MethodCall\MethodCallToPropertyFetchRector;
use Rector\Transform\ValueObject\MethodCallToPropertyFetch;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->ruleWithConfiguration(RenameClassRector::class, [
        'Neos\\ContentRepository\\Domain\\Model\\NodeInterface' => Node::class,
        'Neos\\ContentRepository\\Domain\\Projection\\Content\\NodeInterface' => Node::class,
        'Neos\\ContentRepository\\Domain\\Projection\\Content\\TraversableNodeInterface' => Node::class,
    ]);


    /** @var $methodCallToPropertyFetches MethodCallToPropertyFetch[] */
    $methodCallToPropertyFetches = [];


    /**
     * Neos\ContentRepository\Domain\Model\NodeInterface
     */
    // setName
    // getName
    $methodCallToPropertyFetches[] = new MethodCallToPropertyFetch(Node::class, 'getName', 'nodeName');
    // getLabel -> compatible with ES CR node (nothing to do)
    // setProperty
    // hasProperty -> compatible with ES CR Node (nothing to do)
    // getProperty -> compatible with ES CR Node (nothing to do)
    // removeProperty
    // getProperties -> PropertyCollectionInterface
    $methodCallToPropertyFetches[] = new MethodCallToPropertyFetch(Node::class, 'getProperties', 'properties');
    // getPropertyNames
    // setContentObject -> DEPRECATED / NON-FUNCTIONAL
    // getContentObject -> DEPRECATED / NON-FUNCTIONAL
    // unsetContentObject -> DEPRECATED / NON-FUNCTIONAL
    // setNodeType
    // getNodeType: NodeType
    $methodCallToPropertyFetches[] = new MethodCallToPropertyFetch(Node::class, 'getNodeType', 'nodeType');
    // setHidden
    // isHidden
    $rectorConfig->rule(NodeIsHiddenRector::class);
    // setHiddenBeforeDateTime
    // getHiddenBeforeDateTime
    // setHiddenAfterDateTime
    // getHiddenAfterDateTime
    // getHiddenAfterDateTime
    // setHiddenInIndex
    // isHiddenInIndex
    // isHiddenInIndex
    // setAccessRoles
    // getAccessRoles
    // getPath
    // getContextPath
    // getDepth
    // setWorkspace -> internal
    // getWorkspace
    // getIdentifier
    $methodCallToPropertyFetches[] = new MethodCallToPropertyFetch(Node::class, 'getIdentifier', 'nodeAggregateIdentifier');
    // setIndex -> internal
    // getIndex
    // getParent -> Node
    // getParentPath - deprecated
    // createNode
    // createSingleNode -> internal
    // createNodeFromTemplate
    // getNode(relative path) - deprecated
    // getPrimaryChildNode() - deprecated
    // getChildNodes($nodeTypeFilter, $limit, $offset) - deprecated
    $rectorConfig->rule(NodeGetChildNodesRector::class);
        // - TODO: NodeTypeFilter
        // - TODO: Limit
        // - TODO: Offset
    // hasChildNodes($nodeTypeFilter) - deprecated
    // remove()
    // setRemoved()
    // isRemoved()
    // isVisible()
    // isAccessible()
    // hasAccessRestrictions()
    // isNodeTypeAllowedAsChildNode()
    // moveBefore()
    // moveAfter()
    // moveInto()
    // copyBefore()
    // copyAfter()
    // copyInto()
    // getNodeData()
    // getContext()
    // getDimensions()
    // createVariantForContext()
    // isAutoCreated()
    // getOtherNodeVariants()

    /**
     * Neos\ContentRepository\Domain\Projection\Content\NodeInterface
     */
    // isRoot()
    // isTethered()
    // getContentStreamIdentifier() -> threw exception in <= Neos 8.0 - so nobody could have used this
    // getNodeAggregateIdentifier()
    $methodCallToPropertyFetches[] = new MethodCallToPropertyFetch(Node::class, 'getNodeAggregateIdentifier', 'nodeAggregateIdentifier');
    // getNodeTypeName()
    $methodCallToPropertyFetches[] = new MethodCallToPropertyFetch(Node::class, 'getNodeTypeName', 'nodeTypeName');
    // getNodeType() ** (included/compatible in old NodeInterface)
    // getNodeName()
    $methodCallToPropertyFetches[] = new MethodCallToPropertyFetch(Node::class, 'getNodeName', 'nodeName');
    // getOriginDimensionSpacePoint() -> threw exception in <= Neos 8.0 - so nobody could have used this
    // getProperties() ** (included/compatible in old NodeInterface)
    // getProperty() ** (included/compatible in old NodeInterface)
    // hasProperty() ** (included/compatible in old NodeInterface)
    // getLabel() ** (included/compatible in old NodeInterface)

    /**
     * Neos\ContentRepository\Domain\Projection\Content\TraversableNodeInterface
     */
    // getDimensionSpacePoint() -> threw exception in <= Neos 8.0 - so nobody could have used this
    // findParentNode() -> TraversableNodeInterface
    // findNodePath() -> NodePath
    // findNamedChildNode(NodeName $nodeName): TraversableNodeInterface;
    // findChildNodes(NodeTypeConstraints $nodeTypeConstraints = null, int $limit = null, int $offset = null): TraversableNodes;
    // countChildNodes(NodeTypeConstraints $nodeTypeConstraints = null): int;
    // findReferencedNodes(): TraversableNodes;
    // findNamedReferencedNodes(PropertyName $edgeName): TraversableNodes;
    // findReferencingNodes() -> threw exception in <= Neos 8.0 - so nobody could have used this
    // findNamedReferencingNodes() -> threw exception in <= Neos 8.0 - so nobody could have used this


    $rectorConfig->ruleWithConfiguration(MethodCallToPropertyFetchRector::class, $methodCallToPropertyFetches);

    // Should run LAST - as other rules above might create $this->contentRepositoryRegistry calls.
    $rectorConfig->rule(InjectContentRepositoryRegistryIfNeededRector::class);
};
