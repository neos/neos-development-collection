.. _security:

===============================
Permissions & Access Management
===============================

Introduction
============

A common requirement, especially for larger websites with many editors, is the possibility to selectively control
access to certain backend tools and parts of the content. For example so that editors can only edit certain pages
or content types or that they are limited to specific workspaces. These access restrictions are used to enforce
certain workflows and to reduce complexity for editors.

Neos provides a way to define Access Control Lists (ACL) in a very fine-grained manner, enabling the following
use cases:

- hide parts of the node tree completely (useful for multi-site websites and frontend-login)
- show only specific Backend Modules
- allow to create/edit only specific Node Types
- allow to only edit parts of the Node Tree
- allow to only edit a specific dimension

The underlying security features of Flow provide the following generic possibilities in addition:

- protect arbitrary method calls
- define the visibility of arbitrary elements depending on the authenticated user

Privilege targets define what is restricted, they are defined by combining privileges with matchers, to address
specific parts of the node tree. A user is assigned to one or more specific roles, defining who the user is. For
each role, a list of privileges is specified, defining the exact permissions of users assigned to each role.

In the Neos user interface, it is possible to assign a list of multiple roles to a user. This allows to define the
permissions a user actually has on a fine-grained level. Additionally, the user management module has basic support
for multiple accounts per user: a user may, for example, have one account for backend access and another one for
access to a member-only area on the website.

As a quick example, a privilege target giving access to a specific part of the node tree looks as follows:

.. code-block:: yaml

  'TYPO3\TYPO3CR\Security\Authorization\Privilege\NodeTreePrivilege':
    'YourSite:EditWebsitePart':
      matcher: 'isDescendantNodeOf("c1e528e2-b495-0622-e71c-f826614ef287")'

Adjusting and defining roles
============================

Neos comes with a number of predefined roles that can be assigned to users:

+-----------------------------+-----------------------------+--------------------------------------------------------+
| Role                        | Parent role(s)              | Description                                            |
+=============================+=============================+========================================================+
| TYPO3.TYPO3CR:Administrator |                             | A no-op role for future use                            |
+-----------------------------+-----------------------------+--------------------------------------------------------+
| TYPO3.Neos:AbstractEditor   | TYPO3.TYPO3CR:Administrator | Grants the very basic things needed to use Neos at all |
+-----------------------------+-----------------------------+--------------------------------------------------------+
| TYPO3.Neos:LivePublisher    |                             | A "helper role" to allow publishing to the live        |
|                             |                             | workspace                                              |
+-----------------------------+-----------------------------+--------------------------------------------------------+
| TYPO3.Neos:RestrictedEditor | TYPO3.Neos:AbstractEditor   | Allows to edit content but not publish to the live     |
|                             |                             | workspace                                              |
+-----------------------------+-----------------------------+--------------------------------------------------------+
| TYPO3.Neos:Editor           | TYPO3.Neos:AbstractEditor   | Allows to edit and publish content                     |
|                             |                             |                                                        |
|                             | TYPO3.Neos:LivePublisher    |                                                        |
+-----------------------------+-----------------------------+--------------------------------------------------------+
| TYPO3.Neos:Administrator    | TYPO3.Neos:Editor           | Everything the Editor can do, plus admin things        |
+-----------------------------+-----------------------------+--------------------------------------------------------+

To adjust permissions for your editors, you can of course just adjust the existing roles (`TYPO3.Neos:RestrictedEditor`
and `TYPO3.Neos:Editor` in most cases). If you need different sets of permissions, you will need to define your own
custom roles, though.

Those custom roles should inherit from RestrictedEditor or Editor and then grant access to the additional privilege
targets you define (see below).

Here is an example for a role (limiting editing to a specific language) that shows this:

.. code-block:: yaml

  privilegeTargets:
    'TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\EditNodePrivilege':
      # this privilegeTarget is defined to switch to a "whitelist" approach
      'Acme.Com:EditAllNodes':
        matcher: 'TRUE'

      'Acme.Com:EditFinnish':
        matcher: 'isInDimensionPreset("language", "fi")'

  roles:
    'TYPO3.Neos:Editor':
      privileges:
        -
          privilegeTarget: 'Acme.Com:EditAllNodes'
          permission: GRANT

    'Acme.Com:FinnishEditor':
      parentRoles: ['TYPO3.Neos:RestrictedEditor']
      privileges:
        -
          privilegeTarget: 'Acme.Com:EditFinnish'
          permission: GRANT

Node Privileges
===============

Node privileges define what can be restricted in relation to accessing and editing nodes. In combination with matchers
(see the next section) they allow to define privilege targets that can be granted or denied for specific roles.

.. note::
  This is a blacklist by default, so the privilege won't match if one of the conditions don't match. So the example:

  .. code-block:: yaml

    privilegeTargets:
      'TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\CreateNodePrivilege':
        'Some.Package:SomeIdentifier':
          matcher: >-
            isDescendantNodeOf("c1e528e2-b495-0622-e71c-f826614ef287")
            && createdNodeIsOfType("TYPO3.Neos.NodeTypes:Text")

  will actually only affect nodes of that type (and subtypes). All users will still be able to create other node types,
  unless you also add a more generic privilege target:

  .. code-block:: yaml

    privilegeTargets:
      'TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\CreateNodePrivilege':
        'Some.Package:SomeIdentifier':
          matcher: isDescendantNodeOf("c1e528e2-b495-0622-e71c-f826614ef287")

  That will be abstained by default. It's the same with MethodPrivileges, but with those we abstain all actions by
  default (in Neos that is).

NodeTreePrivilege
-----------------

A privilege that prevents matching document nodes to appear in the Navigate Component. It also prevents editing of
those nodes in case the editor navigates to a node without using the Navigate Component (e.g. by entering the URL
directly).

Usage example:

.. code-block:: yaml

  privilegeTargets:
    'TYPO3\Neos\Security\Authorization\Privilege\NodeTreePrivilege':
      'Some.Package:SomeIdentifier':
        matcher: 'isDescendantNodeOf("c1e528e2-b495-0622-e71c-f826614ef287")'

This defines a privilege that intercepts access to the specified node (and all of its child nodes) in the node tree.

EditNodePropertyPrivilege
-------------------------

A privilege that targets editing of node properties.

Usage example:

.. code-block:: yaml

  privilegeTargets:
    'TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\EditNodePropertyPrivilege':
      'Some.Package:SomeIdentifier':
        matcher: >-
          isDescendantNodeOf("c1e528e2-b495-0622-e71c-f826614ef287")
          && nodePropertyIsIn(["hidden", "name"])

This defines a privilege target that intercepts editing the "hidden" and "name" properties of the specified node
(and all of its child nodes).

ReadNodePropertyPrivilege
-------------------------

A privilege that targets reading of node properties.

Usage example:

.. code-block:: yaml

  'TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\ReadNodePropertyPrivilege':
    'Some.Package:SomeIdentifier':
      matcher: 'isDescendantNodeOf("c1e528e2-b495-0622-e71c-f826614ef287")'

This defines a privilege target that intercepts reading any property of the specified node (and all of its child-nodes).

RemoveNodePrivilege
-------------------

A privilege that targets deletion of nodes.

Usage example:

.. code-block:: yaml

  privilegeTargets:
   'TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\RemoveNodePrivilege':
     'Some.Package:SomeIdentifier':
       matcher: 'isDescendantNodeOf("c1e528e2-b495-0622-e71c-f826614ef287")'

This defines a privilege target that intercepts deletion of the specified node (and all of its child-nodes).

CreateNodePrivilege
-------------------

A privilege that targets creation of nodes.

Usage example:

.. code-block:: yaml

  privilegeTargets:
    'TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\CreateNodePrivilege':
      'Some.Package:SomeIdentifier':
        matcher: >-
          isDescendantNodeOf("c1e528e2-b495-0622-e71c-f826614ef287")
          && createdNodeIsOfType("TYPO3.Neos.NodeTypes:Text")

This defines a privilege target that intercepts creation of Text nodes in the specified node (and all of its child
nodes).

EditNodePrivilege
-----------------

A privilege that targets editing of nodes.

Usage example:

.. code-block:: yaml

  privilegeTargets:
   'TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\EditNodePrivilege':
      'Some.Package:SomeIdentifier':
        matcher: >-
          isDescendantNodeOf("c1e528e2-b495-0622-e71c-f826614ef287")
          && nodeIsOfType("TYPO3.Neos.NodeTypes:Text")

This defines a privilege target that intercepts editing of Text nodes on the specified node (and all of its child
nodes).

ReadNodePrivilege
-----------------

The ReadNodePrivilege is used to limit access to certain parts of the node tree:

With this configuration, the node with the identifier c1e528e2-b495-0622-e71c-f826614ef287 and all its child nodes will
be hidden from the system unless explicitly granted to the current user (by assigning ``SomeRole``):

.. code-block:: yaml

  privilegeTargets:
    'TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\ReadNodePrivilege':
      'Some.Package:MembersArea':
        matcher: 'isDescendantNodeOf("c1e528e2-b495-0622-e71c-f826614ef287")'

  roles:
    'Some.Package:SomeRole':
      privileges:
        -
          privilegeTarget: 'Some.Package:MembersArea'
          permission: GRANT

Privilege Matchers
==================

The privileges need to be applied to certain nodes to be useful. For this, matchers are used in the policy, written
using Eel. Depending on the privilege, various methods to address nodes are available.

Position in the Node Tree
-------------------------

This allows to match on the position in the node tree. A node matches if it is below the given node or the node itself.

Signature:
  ``isDescendantNodeOf(node-path-or-identifier)``
Parameters:
  * ``node-path-or-identifier`` (string) The nodes' path or identifier
Applicable to:
  matchers of all node privileges

.. note::
 The node path is not reliable because it changes if a node is moved. And the path is not "human-readable" in Neos
 because new nodes get a unique random name. Therefore it is best practice not to rely on the path but on the identifier
 of a node.

NodeType
--------

Matching against the type of a node comes in two flavors. Combining both allows to limit node creation in a
sophisticated way.

The first one allows to match on the type a node has:

Signature:
  ``nodeIsOfType(nodetype-name)``
Parameters:
  * ``node-path-or-identifier`` (string|array) an array of supported node type identifiers or a single node type identifier
Applicable to:
  matchers of all node privileges

Inheritance is taken into account, so that specific types also match if a supertype is given to this matcher.

The second one allows to match on the type of a node that is being created:

Signature:
  ``createdNodeIsOfType(nodetype-identifier)``
Parameters:
  * ``nodetype-identifier`` (string|array) an array of supported node type identifiers or a single node type identifier
Applicable to:
  matchers of the ``CreateNodePrivilege``

This acts on the type of the node that is about to be created.

Workspace Name
--------------

This allows to match against the name of a workspace a node is in.

Signature:
  ``isInWorkspace(workspace-names)``
Parameters:
  * ``workspace-names`` (string|array) an array of workspace names or a single workspace name
Applicable to:
  matchers of all node privileges

Property Name
-------------

This allows to match against the name of a property that is going to be affected.

Signature:
  ``nodePropertyIsIn(property-names)``
Parameters:
  * ``property-names`` (string|array) an array of property names or a single property name
Applicable to:
  matchers of he ``ReadNodePropertyPrivilege`` and the ``EditNodePropertyPrivilege``

Content Dimension
-----------------

This allows to restrict editing based on the content dimension a node is in. Matches if the currently-selected preset
in the passed  dimension ``name`` is one of ``presets``.

Signature:
  ``isInDimensionPreset(name, value)``
Parameters:
  * ``name`` (string) The content dimension name
  * ``presets`` (string|array) The preset of the content dimension
Applicable to:
  matchers of all node privileges

The following example first blocks editing of nodes completely (by defining a privilege target that always matches) and
then defines a privilege target matching all nodes having a value of "de" for the "language" content dimension. That
target is then granted for the "Editor" role.

.. code-block:: yaml

  privilegeTargets:
    'TYPO3\TYPO3CR\Security\Authorization\Privilege\Node\EditNodePrivilege':
      # This privilegeTarget must be defined, so that we switch to a "whitelist" approach
      'Neos.Demo:EditAllNodes':
        matcher: 'TRUE'

      'Neos.Demo:EditGerman':
        matcher: 'isInDimensionPreset("language", "de")'

  roles:
    'TYPO3.Neos:Editor':
      privileges:
        -
          privilegeTarget: 'Neos.Demo:EditGerman'
          permission: GRANT

Restricting Access to Backend Modules
=====================================

Restrict Module Access
----------------------

The available modules are defined in the settings of Neos. Along with those settings privilege targets can be defined.
Those are used to remove the module from the UI if access would not be granted. Here is a shortened example containing
only the relevant parts:

 .. code-block:: yaml

  TYPO3:
   Neos:
     modules:
      management:
        privilegeTarget: 'TYPO3.Neos:Backend.Module.Management'
        submodules:
          workspaces:
            privilegeTarget: 'TYPO3.Neos:Backend.Module.Management.Workspaces'

The targets are defined as usual in the security policy, here is a shortened example:

.. code-block:: yaml

    'TYPO3.Neos:Backend.Module.Management':
      matcher: 'method(TYPO3\Neos\Controller\Module\ManagementController->indexAction())'

    'TYPO3.Neos:Backend.Module.Management.Workspaces':
      matcher: >-
        method(
          TYPO3\Neos\Controller\Module\Management\WorkspacesController
          ->(publishNode|discardNode|publishOrDiscardNodes)Action()
        ) || method(TYPO3\Neos\Service\Controller\AbstractServiceController->(error)Action())

Now those privilege targets can be used to grant/deny access for specific roles.

Disable Modules
---------------

To completely disable modules available in the Neos UI a setting can be used:

.. code-block:: yaml

  TYPO3:
    Neos:
      modules:
        management:
          submodules:
            history:
              enabled: FALSE

Limitations
===========

Except for the assignment of roles to users there is no UI for editing security related configuration. Any needed
changes have to be made to the policies in ``Policy.yaml``.

Further Reading
===============

The privileges specific to Neos are built based on top of the Flow security features. Read the corresponding
documentation.

.. we need intersphinx so we can nicely link between manuals…
