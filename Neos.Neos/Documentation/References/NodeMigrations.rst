.. _`node-migrations`:

Node Migration Reference
========================

Node migrations can be used to deal with renamed node types and property names, set missing default values for
properties, adjust content dimensions and more.

Node migrations work by applying **transformations** on nodes. The nodes that will be transformed are selected
through **filters** in migration files.

The Content Repository comes with a number of common transformations:

- ``AddDimensions``
- ``AddNewProperty``
- ``ChangeNodeType``
- ``ChangePropertyValue``
- ``RemoveNode``
- ``RemoveProperty``
- ``RenameDimension``
- ``RenameNode``
- ``RenameProperty``
- ``SetDimensions``
- ``StripTagsOnProperty``

They all implement the ``TYPO3\TYPO3CR\Migration\Transformations\TransformationInterface``. Custom transformations
can be developed against that interface as well, just use the fully qualified class name for those when specifying
which transformation to use.



Migration files
---------------

To use node migrations to adjust a setup to changed configuration, a YAML file is created that configures the
migration by setting up filters to select what nodes are being worked on by transformations. The Content Repository
comes with a number of filters:

- ``DimensionValues``
- ``IsRemoved``
- ``NodeName``
- ``NodeType``
- ``PropertyNotEmpty``
- ``Workspace``

They all implement the ``TYPO3\TYPO3CR\Migration\Filters\FilterInterface``. Custom filters can be developed against
that interface as well, just use the fully qualified class name for those when specifying which filter to use.

Here is an example of a migration, ``Version20140708120530.yaml``, that operates on nodes in the "live" workspace
that are marked as removed and applies the ``RemoveNode`` transformation on them:

.. code-block:: yaml

  up:
    comments: 'Delete removed nodes that were published to "live" workspace'
    warnings: 'There is no way of reverting this migration since the nodes will be deleted in the database.'
    migration:
      -
        filters:
          -
            type: 'IsRemoved'
            settings: []
          -
            type: 'Workspace'
            settings:
              workspaceName: 'live'
        transformations:
          -
            type: 'RemoveNode'
            settings: []

  down:
    comments: 'No down migration available'

Like all migrations the file should be placed in a package inside the ``Migrations/TYPO3CR`` folder where it will be picked
up by the CLI tools provided with the content repository:

- ``./flow node:migrationstatus``
- ``./flow node:migrate``

Use ``./flow help <command>`` to get detailed instructions. The ``migrationstatus`` command also prints a short description
for each migration.



Transformations Reference
-------------------------

AddDimensions
~~~~~~~~~~~~~

Add dimensions on a node. This adds to the existing dimensions, if you need to overwrite existing dimensions, use
SetDimensions.

Options Reference:

``dimensionValues`` (array)
  An array of dimension names and values to set.
``addDefaultDimensionValues`` (boolean)
  Whether to add the default dimension values for all dimensions that were not given.

AddNewProperty
~~~~~~~~~~~~~~

Add a new property with the given value.

Options Reference:

``newPropertyName`` (string)
  The name of the new property to be added.
``value`` (mixed)
  Property value to be set.

ChangeNodeType
~~~~~~~~~~~~~~

Change the node type.

Options Reference:

``newType`` (string)
  The new Node Type to use as a string.

ChangePropertyValue
~~~~~~~~~~~~~~~~~~~

Change the value of a given property.

This can apply two transformations:

- If newValue is set, the value will be set to this, with any occurrences of the ``currentValuePlaceholder`` replaced
  with the current value of the property.
- If search and replace are given, that replacement will be done on the value (after applying the ``newValue``, if set).

This would simply override the existing value:

.. code-block:: yaml

  transformations:
    -
      type: 'ChangePropertyValue'
      settings:
        property: 'title'
        newValue: 'a new value'

This would prefix the existing value:

.. code-block:: yaml

  transformations:
    -
      type: 'ChangePropertyValue'
      settings:
        property: 'title'
        newValue: 'this is a prefix to {current}'

This would prefix existing value and then apply search/replace on the result:

.. code-block:: yaml

  transformations:
    -
      type: 'ChangePropertyValue'
      settings:
        property: 'title'
        newValue: 'this is a prefix to {current}'
        search: 'something'
        replace: 'something else'

And in case your value contains the magic string "{current}" and you need to leav it intact, this would prefix the existing
value but use a different placeholder:

.. code-block:: yaml

  transformations:
    -
      type: 'ChangePropertyValue'
      settings:
        property: 'title'
        newValue: 'this is a prefix to {__my_unique_placeholder}'
        currentValuePlaceholder: '__my_unique_placeholder'

Options Reference:

``property`` (string)
  The name of the property to change.
``newValue`` (string)
  New property value to be set.

  The value of the option ``currentValuePlaceholder`` (defaults to "{current}") will be used to include the current
  property value into the new value.
``search`` (string)
  Search string to replace in current property value.
``replace`` (string)
  Replacement for the search string.
``currentValuePlaceholder`` (string)
  The value of this option (defaults to ``{current}``) will be used to include the current property value into the new
  value.

RemoveNode
~~~~~~~~~~

Removes the node.

RemoveProperty
~~~~~~~~~~~~~~

Remove the property.

Options Reference:

``property`` (string)
  The name of the property to be removed.

RenameDimension
~~~~~~~~~~~~~~~

Rename a dimension.

Options Reference:

``newDimensionName`` (string)
  The new name for the dimension.
``oldDimensionName`` (string)
  The old name of the dimension to rename.

RenameNode
~~~~~~~~~~

Rename a node.

Options Reference:

``newName`` (string)
  The new name for the node.

RenameProperty
~~~~~~~~~~~~~~

Rename a given property.

Options Reference:

``from`` (string)
  The name of the property to change.
``to`` (string)
  The new name for the property to change.


SetDimensions
~~~~~~~~~~~~~
Set dimensions on a node. This always overwrites existing dimensions, if you need to add to existing dimensions, use
AddDimensions.

Options Reference:

``dimensionValues`` (array)
  An array of dimension names and values to set.
``addDefaultDimensionValues`` (boolean)
  Whether to add the default dimension values for all dimensions that were not given.

StripTagsOnProperty
~~~~~~~~~~~~~~~~~~~

Strip all tags on a given property.

Options Reference:

``property`` (string)
  The name of the property to work on.



Filters Reference
-----------------

DimensionValues
~~~~~~~~~~~~~~~

Filter nodes by their dimensions.

Options Reference:

``dimensionValues`` (array)
  The array of dimension values to filter for.
``filterForDefaultDimensionValues`` (boolean)
  Overrides the given dimensionValues with dimension defaults.

IsRemoved
~~~~~~~~~

Selects nodes marked as removed.

NodeName
~~~~~~~~

Selects nodes with the given name.

Options Reference:

``name`` (string)
  The value to compare the node name against, strict equality is checked.

NodeType
~~~~~~~~

Selects nodes by node type.

Options Reference:

``nodeType`` (string)
  The node type name to match on.
``withSubTypes`` (boolean)
  Whether the filter should match also on all subtypes of the configured node type.
  Note: This can only be used with node types still available in the system!
``exclude`` (boolean)
  Whether the filter should exclude the given NodeType instead of including only this node type.

PropertyNotEmpty
~~~~~~~~~~~~~~~~

Filter nodes having the given property and its value not empty.

Options Reference:

``propertyName`` (string)
  The property name to be checked for non-empty value.

Workspace
~~~~~~~~~

Filter nodes by workspace name.

Options Reference:

``workspaceName`` (string)
  The workspace name to match on.
