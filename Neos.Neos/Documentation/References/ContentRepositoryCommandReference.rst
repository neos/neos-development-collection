.. _`ContentRepository Command Reference`:

ContentRepository Command Reference
===================================

This reference was automatically generated from code on 2025-02-11


.. _`ContentRepository Command Reference: AddDimensionShineThrough`:

AddDimensionShineThrough
------------------------

Add a Dimension Space Point Shine-Through;
basically making all content available not just in the source(original) DSP,
but also in the target-DimensionSpacePoint.

NOTE: the Source Dimension Space Point must be a generalization of the target Dimension Space Point.

This is needed if "de" exists, and you want to create a "de_CH" specialization.

NOTE: the target dimension space point must not contain any content.

create(workspaceName, source, target)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) The name of the workspace to perform the operation in
* ``source`` (DimensionSpacePoint) source dimension space point
* ``target`` (DimensionSpacePoint) target dimension space point




.. _`ContentRepository Command Reference: ChangeBaseWorkspace`:

ChangeBaseWorkspace
-------------------

Changes the base workspace of a given workspace, identified by $workspaceName.

create(workspaceName, baseWorkspaceName)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) Name of the affected workspace
* ``baseWorkspaceName`` (WorkspaceName) Name of the new base workspace




.. _`ContentRepository Command Reference: ChangeNodeAggregateName`:

ChangeNodeAggregateName (deprecated)
------------------------------------

All variants in a NodeAggregate have the same (optional) NodeName, which this can be changed here.

Node Names are usually only used for tethered nodes; as then the Node Name is used for querying.
Tethered Nodes cannot be renamed via the command API.

create(workspaceName, nodeAggregateId, newNodeName)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) The workspace in which the operation is to be performed
* ``nodeAggregateId`` (NodeAggregateId) The identifier of the node aggregate to rename
* ``newNodeName`` (NodeName) The new name of the node aggregate


**DEPRECATED** the concept regarding node-names for non-tethered nodes is outdated.




.. _`ContentRepository Command Reference: ChangeNodeAggregateType`:

ChangeNodeAggregateType
-----------------------

The "Change node aggregate type" command

create(workspaceName, nodeAggregateId, newNodeTypeName, strategy)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) The workspace in which the operation is to be performed
* ``nodeAggregateId`` (NodeAggregateId) The unique identifier of the node aggregate to change
* ``newNodeTypeName`` (NodeTypeName) Name of the new node type
* ``strategy`` (NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy) Strategy for conflicts on affected child nodes ({@see NodeAggregateTypeChangeChildConstraintConflictResolutionStrategy})




.. _`ContentRepository Command Reference: CreateNodeAggregateWithNode`:

CreateNodeAggregateWithNode
---------------------------

Creates a new node aggregate with a new node.

The node will be appended as child node of the given `parentNodeId` which must cover the given
`originDimensionSpacePoint`.

create(workspaceName, nodeAggregateId, nodeTypeName, originDimensionSpacePoint, parentNodeAggregateId, succeedingSiblingNodeAggregateId, initialPropertyValues, references)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) The workspace in which the create operation is to be performed
* ``nodeAggregateId`` (NodeAggregateId) The unique identifier of the node aggregate to create
* ``nodeTypeName`` (NodeTypeName) Name of the node type of the new node
* ``originDimensionSpacePoint`` (OriginDimensionSpacePoint) Origin of the new node in the dimension space. Will also be used to calculate a set of dimension points where the new node will cover from the configured specializations.
* ``parentNodeAggregateId`` (NodeAggregateId) The id of the node aggregate underneath which the new node is added
* ``succeedingSiblingNodeAggregateId`` (NodeAggregateId|null, *optional*) Node aggregate id of the node's succeeding sibling (optional). If not given, the node will be added as the parent's first child
* ``initialPropertyValues`` (PropertyValuesToWrite|null, *optional*) The node's initial property values. Will be merged over the node type's default property values
* ``references`` (NodeReferencesToWrite|null, *optional*) Initial references this node will have (optional). If not given, no references are created




.. _`ContentRepository Command Reference: CreateNodeVariant`:

CreateNodeVariant
-----------------

Create a variant of a node in a content stream

Copy a node to another dimension space point respecting further variation mechanisms

create(workspaceName, nodeAggregateId, sourceOrigin, targetOrigin)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) The workspace in which the create operation is to be performed
* ``nodeAggregateId`` (NodeAggregateId) The identifier of the affected node aggregate
* ``sourceOrigin`` (OriginDimensionSpacePoint) Dimension Space Point from which the node is to be copied from
* ``targetOrigin`` (OriginDimensionSpacePoint) Dimension Space Point to which the node is to be copied to




.. _`ContentRepository Command Reference: CreateRootNodeAggregateWithNode`:

CreateRootNodeAggregateWithNode
-------------------------------

Create root node aggregate with node command

A root node has no variants and no origin dimension space point but occupies the whole allowed dimension subspace.
It also has no tethered child nodes.

create(workspaceName, nodeAggregateId, nodeTypeName)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) The workspace in which the root node should be created in
* ``nodeAggregateId`` (NodeAggregateId) The id of the root node aggregate to create
* ``nodeTypeName`` (NodeTypeName) Name of type of the new node to create




.. _`ContentRepository Command Reference: CreateRootWorkspace`:

CreateRootWorkspace
-------------------

Command to create a root workspace.

Also creates a root content stream internally.

create(workspaceName, newContentStreamId)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) Name of the workspace to create
* ``newContentStreamId`` (ContentStreamId) The id of the content stream the new workspace is assigned to initially




.. _`ContentRepository Command Reference: CreateWorkspace`:

CreateWorkspace
---------------

Create a new workspace, based on an existing baseWorkspace

create(workspaceName, baseWorkspaceName, newContentStreamId)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) Unique name of the workspace to create
* ``baseWorkspaceName`` (WorkspaceName) Name of the base workspace
* ``newContentStreamId`` (ContentStreamId) The id of the content stream the new workspace is assigned to initially




.. _`ContentRepository Command Reference: DeleteWorkspace`:

DeleteWorkspace
---------------

Delete a workspace

create(workspaceName)
^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) Name of the workspace to delete




.. _`ContentRepository Command Reference: DisableNodeAggregate`:

DisableNodeAggregate
--------------------

Disable the given node aggregate in the given content stream in a dimension space point using a given strategy

create(workspaceName, nodeAggregateId, coveredDimensionSpacePoint, nodeVariantSelectionStrategy)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) The workspace in which the disable operation is to be performed
* ``nodeAggregateId`` (NodeAggregateId) The identifier of the node aggregate to disable
* ``coveredDimensionSpacePoint`` (DimensionSpacePoint) The covered dimension space point of the node aggregate in which the user intends to disable it
* ``nodeVariantSelectionStrategy`` (NodeVariantSelectionStrategy) The strategy the user chose to determine which specialization variants will also be disabled




.. _`ContentRepository Command Reference: DiscardIndividualNodesFromWorkspace`:

DiscardIndividualNodesFromWorkspace
-----------------------------------

Discard a set of nodes in a workspace

create(workspaceName, nodesToDiscard)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) Name of the affected workspace
* ``nodesToDiscard`` (NodeAggregateIds) Ids of the nodes to be discarded




.. _`ContentRepository Command Reference: DiscardWorkspace`:

DiscardWorkspace
----------------

Discard a workspace's changes

create(workspaceName)
^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) Name of the affected workspace




.. _`ContentRepository Command Reference: EnableNodeAggregate`:

EnableNodeAggregate
-------------------

Enable the given node aggregate in the given content stream in a dimension space point using a given strategy

create(workspaceName, nodeAggregateId, coveredDimensionSpacePoint, nodeVariantSelectionStrategy)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) The content stream in which the enable operation is to be performed
* ``nodeAggregateId`` (NodeAggregateId) The identifier of the node aggregate to enable
* ``coveredDimensionSpacePoint`` (DimensionSpacePoint) The covered dimension space point of the node aggregate in which the user intends to enable it
* ``nodeVariantSelectionStrategy`` (NodeVariantSelectionStrategy) The strategy the user chose to determine which specialization variants will also be enabled




.. _`ContentRepository Command Reference: MoveDimensionSpacePoint`:

MoveDimensionSpacePoint
-----------------------

Move a dimension space point to a new location; basically moving all content to the new dimension space point.

This is used to *rename* dimension space points, e.g. from "de" to "de_DE".

NOTE: the target dimension space point must not contain any content.

create(workspaceName, source, target)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) The name of the workspace to perform the operation in
* ``source`` (DimensionSpacePoint) source dimension space point
* ``target`` (DimensionSpacePoint) target dimension space point




.. _`ContentRepository Command Reference: MoveNodeAggregate`:

MoveNodeAggregate
-----------------

The "Move node aggregate" command

In `contentStreamId`
and `dimensionSpacePoint`,
move node aggregate `nodeAggregateId`
into `newParentNodeAggregateId` (or keep the current parent)
between `newPrecedingSiblingNodeAggregateId`
and `newSucceedingSiblingNodeAggregateId` (or as last of all siblings)
using `relationDistributionStrategy`

Why can you specify **both** newPrecedingSiblingNodeAggregateId
and newSucceedingSiblingNodeAggregateId?

- it can happen that in one subgraph, only one of these match.
- See the PHPDoc of the attributes (a few lines down) for the exact behavior.

create(workspaceName, dimensionSpacePoint, nodeAggregateId, relationDistributionStrategy, newParentNodeAggregateId, newPrecedingSiblingNodeAggregateId, newSucceedingSiblingNodeAggregateId)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) The workspace in which the move operation is to be performed
* ``dimensionSpacePoint`` (DimensionSpacePoint) This is one of the *covered* dimension space points of the node aggregate and not necessarily one of the occupied ones. This allows us to move virtual specializations only when using the scatter strategy
* ``nodeAggregateId`` (NodeAggregateId) The id of the node aggregate to move
* ``relationDistributionStrategy`` (RelationDistributionStrategy) The relation distribution strategy to be used ({@see RelationDistributionStrategy}).
* ``newParentNodeAggregateId`` (NodeAggregateId|null, *optional*) The id of the new parent node aggregate. If given, it enforces that all nodes in the given aggregate are moved into nodes of the parent aggregate, even if the given siblings belong to other parents. In latter case, those siblings are ignored
* ``newPrecedingSiblingNodeAggregateId`` (NodeAggregateId|null, *optional*) The id of the new preceding sibling node aggregate. If given and no successor found, it is attempted to insert the moved nodes right after nodes of this aggregate. In dimension space points this aggregate does not cover, other siblings, in order of proximity, are tried to be used instead
* ``newSucceedingSiblingNodeAggregateId`` (NodeAggregateId|null, *optional*) The id of the new succeeding sibling node aggregate. If given, it is attempted to insert the moved nodes right before nodes of this aggregate. In dimension space points this aggregate does not cover, the preceding sibling is tried to be used instead




.. _`ContentRepository Command Reference: PublishIndividualNodesFromWorkspace`:

PublishIndividualNodesFromWorkspace
-----------------------------------

Publish a set of nodes in a workspace

create(workspaceName, nodesToPublish)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) Name of the affected workspace
* ``nodesToPublish`` (NodeAggregateIds) Ids of the nodes to publish or discard




.. _`ContentRepository Command Reference: PublishWorkspace`:

PublishWorkspace
----------------

Publish a workspace

create(workspaceName)
^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) Name of the workspace to publish




.. _`ContentRepository Command Reference: RebaseWorkspace`:

RebaseWorkspace
---------------

Rebase a workspace

create(workspaceName)
^^^^^^^^^^^^^^^^^^^^^




.. _`ContentRepository Command Reference: RemoveNodeAggregate`:

RemoveNodeAggregate
-------------------

The "Remove node aggregate" command

create(workspaceName, nodeAggregateId, coveredDimensionSpacePoint, nodeVariantSelectionStrategy)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) The workspace in which the remove operation is to be performed
* ``nodeAggregateId`` (NodeAggregateId) The identifier of the node aggregate to remove
* ``coveredDimensionSpacePoint`` (DimensionSpacePoint) One of the dimension space points covered by the node aggregate in which the user intends to remove it
* ``nodeVariantSelectionStrategy`` (NodeVariantSelectionStrategy) The strategy the user chose to determine which specialization variants will also be removed




.. _`ContentRepository Command Reference: SetNodeProperties`:

SetNodeProperties
-----------------

Add property values for a given node.

The properties will not be replaced but will be merged via the existing ones by the projection.
A null value will cause to unset a nodes' property.

The property values support arbitrary types (but must match the NodeType's property types -
this is validated in the command handler).

Internally, this object is converted into a {@see SetSerializedNodeProperties} command, which is
then processed and stored.

create(workspaceName, nodeAggregateId, originDimensionSpacePoint, propertyValues)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) The workspace in which the set properties operation is to be performed
* ``nodeAggregateId`` (NodeAggregateId) The id of the node aggregate to set the properties for
* ``originDimensionSpacePoint`` (OriginDimensionSpacePoint) The dimension space point the properties should be changed in
* ``propertyValues`` (PropertyValuesToWrite) Names and (unserialized) values of properties to set, or unset if the value is null




.. _`ContentRepository Command Reference: SetNodeReferences`:

SetNodeReferences
-----------------

Create a named reference from source to one or multiple destination nodes.

The previously set references will be replaced by this command and not merged.

Internally, this object is converted into a {@see SetSerializedNodeReferences} command, which is
then processed and stored.

create(workspaceName, sourceNodeAggregateId, sourceOriginDimensionSpacePoint, references)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) The workspace in which the create operation is to be performed
* ``sourceNodeAggregateId`` (NodeAggregateId) The identifier of the node aggregate to set references
* ``sourceOriginDimensionSpacePoint`` (OriginDimensionSpacePoint) The dimension space for which the references should be set
* ``references`` (NodeReferencesToWrite) Unserialized reference(s) to set




.. _`ContentRepository Command Reference: TagSubtree`:

TagSubtree
----------

Add a {@see SubtreeTag} to a node aggregate and its descendants

create(workspaceName, nodeAggregateId, coveredDimensionSpacePoint, nodeVariantSelectionStrategy, tag)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) The workspace in which the tagging operation is to be performed
* ``nodeAggregateId`` (NodeAggregateId) The identifier of the node aggregate to tag
* ``coveredDimensionSpacePoint`` (DimensionSpacePoint) The covered dimension space point of the node aggregate in which the user intends to tag it
* ``nodeVariantSelectionStrategy`` (NodeVariantSelectionStrategy) The strategy the user chose to determine which specialization variants will also be tagged
* ``tag`` (SubtreeTag) The tag to add to the Subtree




.. _`ContentRepository Command Reference: UntagSubtree`:

UntagSubtree
------------

Remove a {@see SubtreeTag} from a node aggregate and its descendants.

Note: This will remove the tag from the node aggregate and all inherited instances. If the same tag is added for another Subtree below this aggregate, this will still be set!

create(workspaceName, nodeAggregateId, coveredDimensionSpacePoint, nodeVariantSelectionStrategy, tag)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) The workspace in which the remove tag operation is to be performed
* ``nodeAggregateId`` (NodeAggregateId) The identifier of the node aggregate to remove the tag from
* ``coveredDimensionSpacePoint`` (DimensionSpacePoint) The covered dimension space point of the node aggregate in which the user intends to remove the tag
* ``nodeVariantSelectionStrategy`` (NodeVariantSelectionStrategy) The strategy the user chose to determine which specialization variants will also be untagged
* ``tag`` (SubtreeTag) The tag to remove from the node aggregate




.. _`ContentRepository Command Reference: UpdateRootNodeAggregateDimensions`:

UpdateRootNodeAggregateDimensions
---------------------------------

Change visibility of the root node aggregate. A root node aggregate must be visible in all
configured dimensions.

Needed when configured dimensions change.

create(workspaceName, nodeAggregateId)
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

* ``workspaceName`` (WorkspaceName) The workspace which the dimensions should be updated in
* ``nodeAggregateId`` (NodeAggregateId) The id of the node aggregate that should be updated



