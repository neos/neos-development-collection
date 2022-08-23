<?php
declare (strict_types=1);

use Neos\ContentRepository\Rector\Rules\InjectContentRepositoryRegistryIfNeededRector;
use Neos\ContentRepository\Rector\Rules\MethodCallToWarningCommentRector;
use Neos\ContentRepository\Rector\Rules\NodeGetChildNodesRector;
use Neos\ContentRepository\Projection\ContentGraph\Node;
use Neos\ContentRepository\Rector\Rules\NodeGetContextGetWorkspaceNameRector;
use Neos\ContentRepository\Rector\Rules\NodeGetContextGetWorkspaceRector;
use Neos\ContentRepository\Rector\Rules\NodeGetDimensionsRector;
use Neos\ContentRepository\Rector\Rules\NodeGetPathRector;
use Neos\ContentRepository\Rector\Rules\NodeIsHiddenRector;
use Neos\ContentRepository\Rector\ValueObject\MethodCallToWarningComment;
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

    /** @var $methodCallToWarningComments MethodCallToWarningComment[] */
    $methodCallToWarningComments = [];


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
    $methodCallToWarningComments[] = new MethodCallToWarningComment(Node::class, 'setContentObject', '!! Node::setContentObject() is not supported by the new CR. Referencing objects can be done by storing them in Node::properties (and the serialization/deserialization is extensible).');
    // getContentObject -> DEPRECATED / NON-FUNCTIONAL
    $methodCallToWarningComments[] = new MethodCallToWarningComment(Node::class, 'getContentObject', '!! Node::getContentObject() is not supported by the new CR. Referencing objects can be done by storing them in Node::properties (and the serialization/deserialization is extensible).');
    // unsetContentObject -> DEPRECATED / NON-FUNCTIONAL
    $methodCallToWarningComments[] = new MethodCallToWarningComment(Node::class, 'unsetContentObject', '!! Node::unsetContentObject() is not supported by the new CR. Referencing objects can be done by storing them in Node::properties (and the serialization/deserialization is extensible).');
    // setNodeType
    // getNodeType: NodeType
    $methodCallToPropertyFetches[] = new MethodCallToPropertyFetch(Node::class, 'getNodeType', 'nodeType');
    // setHidden
    // isHidden
    $rectorConfig->rule(NodeIsHiddenRector::class);
    // setHiddenBeforeDateTime
    $methodCallToWarningComments[] = new MethodCallToWarningComment(Node::class, 'setHiddenBeforeDateTime', '!! Node::setHiddenBeforeDateTime() is not supported by the new CR. Timed publishing will be implemented not on the read model, but by dispatching commands at a given time.');
    // getHiddenBeforeDateTime
    $methodCallToWarningComments[] = new MethodCallToWarningComment(Node::class, 'getHiddenBeforeDateTime', '!! Node::getHiddenBeforeDateTime() is not supported by the new CR. Timed publishing will be implemented not on the read model, but by dispatching commands at a given time.');
    // setHiddenAfterDateTime
    $methodCallToWarningComments[] = new MethodCallToWarningComment(Node::class, 'setHiddenAfterDateTime', '!! Node::setHiddenAfterDateTime() is not supported by the new CR. Timed publishing will be implemented not on the read model, but by dispatching commands at a given time.');
    // getHiddenAfterDateTime
    $methodCallToWarningComments[] = new MethodCallToWarningComment(Node::class, 'getHiddenAfterDateTime', '!! Node::getHiddenAfterDateTime() is not supported by the new CR. Timed publishing will be implemented not on the read model, but by dispatching commands at a given time.');
    // getHiddenAfterDateTime
    $methodCallToWarningComments[] = new MethodCallToWarningComment(Node::class, 'getHiddenAfterDateTime', '!! Node::getHiddenAfterDateTime() is not supported by the new CR. Timed publishing will be implemented not on the read model, but by dispatching commands at a given time.');
    // setHiddenInIndex
    // isHiddenInIndex
    // isHiddenInIndex
    // setAccessRoles
    $methodCallToWarningComments[] = new MethodCallToWarningComment(Node::class, 'setAccessRoles', '!! Node::setAccessRoles() is not supported by the new CR.');
    // getAccessRoles
    $methodCallToWarningComments[] = new MethodCallToWarningComment(Node::class, 'getAccessRoles', '!! Node::getAccessRoles() is not supported by the new CR.');
    // getPath
    $rectorConfig->rule(NodeGetPathRector::class);
    // getContextPath
        // - NodeAddress + LOG (WARNING)
    // getDepth
    // setWorkspace -> internal
    $methodCallToWarningComments[] = new MethodCallToWarningComment(Node::class, 'setWorkspace', '!! Node::setWorkspace() was always internal, and the workspace system has been fundamentally changed with the new CR. Try to rewrite your code around Content Streams.');
    // getWorkspace
    $methodCallToWarningComments[] = new MethodCallToWarningComment(Node::class, 'getWorkspace', '!! Node::getWorkspace() does not make sense anymore concept-wise. In Neos < 9, it pointed to the workspace where the node was *at home at*. Now, the closest we have here is the node identity.');
    // getIdentifier
    $methodCallToPropertyFetches[] = new MethodCallToPropertyFetch(Node::class, 'getIdentifier', 'nodeAggregateIdentifier');
    // setIndex -> internal
    $methodCallToWarningComments[] = new MethodCallToWarningComment(Node::class, 'setIndex', '!! Node::setIndex() was always internal. To reorder nodes, use the "MoveNodeAggregate" command');
    // getIndex
    $methodCallToWarningComments[] = new MethodCallToWarningComment(Node::class, 'getIndex', '!! Node::getIndex() is not supported. You can fetch all siblings and inspect the ordering');
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
    $methodCallToWarningComments[] = new MethodCallToWarningComment(Node::class, 'isRemoved', '!! Node::isRemoved() - the new CR *never* returns removed nodes; so you can simplify your code and just assume removed == FALSE in all scenarios.');
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
    $methodCallToWarningComments[] = new MethodCallToWarningComment(Node::class, 'getNodeData', '!! Node::getNodeData() - the new CR is not based around the concept of NodeData anymore. You need to rewrite your code here.');
    // getContext()
    // getContext()->getWorkspace()
    $rectorConfig->rule(NodeGetContextGetWorkspaceRector::class);
    // getContext()->getWorkspaceName()
    $rectorConfig->rule(NodeGetContextGetWorkspaceNameRector::class);
    // getContext()->getRootNode()
    // getDimensions()
    $rectorConfig->rule(NodeGetDimensionsRector::class);
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
    $rectorConfig->ruleWithConfiguration(MethodCallToWarningCommentRector::class, $methodCallToWarningComments);

    // Should run LAST - as other rules above might create $this->contentRepositoryRegistry calls.
    $rectorConfig->rule(InjectContentRepositoryRegistryIfNeededRector::class);
};
