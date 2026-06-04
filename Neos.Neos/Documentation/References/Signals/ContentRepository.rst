.. _`Content Repository Signals Reference`:

Content Repository Signals Reference
====================================

This reference was automatically generated from code on 2026-06-04


.. _`Content Repository Signals Reference: Context (``Neos\ContentRepository\Domain\Service\Context``)`:

Context (``Neos\ContentRepository\Domain\Service\Context``)
-----------------------------------------------------------

This class contains the following signals.

beforeAdoptNode
^^^^^^^^^^^^^^^



afterAdoptNode
^^^^^^^^^^^^^^








.. _`Content Repository Signals Reference: Node (``Neos\ContentRepository\Domain\Model\Node``)`:

Node (``Neos\ContentRepository\Domain\Model\Node``)
---------------------------------------------------

This class contains the following signals.

beforeNodeMove
^^^^^^^^^^^^^^



afterNodeMove
^^^^^^^^^^^^^



beforeNodeCopy
^^^^^^^^^^^^^^



afterNodeCopy
^^^^^^^^^^^^^



nodePathChanged
^^^^^^^^^^^^^^^

Signals that the node path has been changed.

beforeNodeCreate
^^^^^^^^^^^^^^^^

Signals that a node will be created.

afterNodeCreate
^^^^^^^^^^^^^^^

Signals that a node was created.

nodeAdded
^^^^^^^^^

Signals that a node was added.

nodeUpdated
^^^^^^^^^^^

Signals that a node was updated.

nodeRemoved
^^^^^^^^^^^

Signals that a node was removed.

beforeNodePropertyChange
^^^^^^^^^^^^^^^^^^^^^^^^

Signals that the property of a node will be changed.

nodePropertyChanged
^^^^^^^^^^^^^^^^^^^

Signals that the property of a node was changed.






.. _`Content Repository Signals Reference: NodeData (``Neos\ContentRepository\Domain\Model\NodeData``)`:

NodeData (``Neos\ContentRepository\Domain\Model\NodeData``)
-----------------------------------------------------------

This class contains the following signals.

nodePathChanged
^^^^^^^^^^^^^^^

Signals that a node has changed its path.






.. _`Content Repository Signals Reference: NodeDataRepository (``Neos\ContentRepository\Domain\Repository\NodeDataRepository``)`:

NodeDataRepository (``Neos\ContentRepository\Domain\Repository\NodeDataRepository``)
------------------------------------------------------------------------------------

This class contains the following signals.

repositoryObjectsPersisted
^^^^^^^^^^^^^^^^^^^^^^^^^^

Signals that persistEntities() in this repository finished correctly.






.. _`Content Repository Signals Reference: PaginateController (``Neos\ContentRepository\ViewHelpers\Widget\Controller\PaginateController``)`:

PaginateController (``Neos\ContentRepository\ViewHelpers\Widget\Controller\PaginateController``)
------------------------------------------------------------------------------------------------

This class contains the following signals.

viewResolved
^^^^^^^^^^^^

Emit that the view is resolved. The passed ViewInterface reference,
gives the possibility to add variables to the view,
before passing it on to further rendering






.. _`Content Repository Signals Reference: PublishingService (``Neos\ContentRepository\Domain\Service\PublishingService``)`:

PublishingService (``Neos\ContentRepository\Domain\Service\PublishingService``)
-------------------------------------------------------------------------------

This class contains the following signals.

nodePublished
^^^^^^^^^^^^^

Signals that a node has been published.

The signal emits the source node and target workspace, i.e. the node contains its source
workspace.

nodeDiscarded
^^^^^^^^^^^^^

Signals that a node has been discarded.

The signal emits the node that has been discarded.






.. _`Content Repository Signals Reference: Workspace (``Neos\ContentRepository\Domain\Model\Workspace``)`:

Workspace (``Neos\ContentRepository\Domain\Model\Workspace``)
-------------------------------------------------------------

This class contains the following signals.

baseWorkspaceChanged
^^^^^^^^^^^^^^^^^^^^

Emits a signal after the base workspace has been changed

beforeNodePublishing
^^^^^^^^^^^^^^^^^^^^

Emits a signal just before a node is being published

The signal emits the source node and target workspace, i.e. the node contains its source
workspace.

afterNodePublishing
^^^^^^^^^^^^^^^^^^^

Emits a signal when a node has been published.

The signal emits the source node and target workspace, i.e. the node contains its source
workspace.





