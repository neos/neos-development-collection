.. _`node-migrations`:

Node Migration Reference
========================

Node migrations can be used to deal with renamed node types and property names, set missing default values for
properties, adjust content dimensions and more.

Node migrations work by applying **transformations** on nodes. The nodes that will be transformed are selected
through **filters** in migration files.

The Content Repository comes with a number of common transformations:

- ``AddDimensionShineThrough``
- ``AddNewProperty``
- ``ChangeNodeType``
- ``ChangePropertyValue``
- ``MoveDimensionSpacePoint``
- ``RemoveNode``
- ``RemoveProperty``
- ``RenameNodeAggregate``
- ``RenameProperty``
- ``StripTagsOnProperty``

They all implement the ``Neos\ContentRepository\NodeMigration\Transformation\TransformationFactoryInterface``. Custom transformations
can be developed against that interface as well, just use the fully qualified class name for those when specifying
which transformation to use.



Migration files
---------------

To use node migrations to adjust a setup to changed configuration, a YAML file is created that configures the
migration by setting up filters to select what nodes are being worked on by transformations. The Content Repository
comes with a number of filters:

- ``DimensionSpacePoints``
- ``NodeName``
- ``NodeType``
- ``PropertyNotEmpty``
- ``PropertyValue``

They all implement the ``Neos\ContentRepository\Migration\Filters\FilterInterface``. Custom filters can be developed against
that interface as well, just use the fully qualified class name for those when specifying which filter to use.

Here is an example of a migration that operates on all nodes with nodetype `Neos.ContentRepository.Testing:Document` and
changes their property name form `text` to `newText`:

.. code-block:: yaml

  comments: 'Rename the property of all Neos.ContentRepository.Testing:Document nodes'
  migration:
    -
      filters:
        -
          type: 'NodeType'
          settings:
            nodeType: 'Neos.ContentRepository.Testing:Document'
      transformations:
        -
          type: 'RenameProperty'
          settings:
            from: 'text'
            to: 'newText'

Like all migrations the file should be placed in a package inside the ``Migrations/ContentRepository`` folder where it will be picked
up by the CLI tools provided with the content repository:

- ``./flow nodemigration:list``
- ``./flow nodemigration:execute``

Use ``./flow help <command>`` to get detailed instructions. The ``nodemigration:list`` command also prints a short description
for each migration.


Transformations Reference
-------------------------

AddDimensionShineThrough
~~~~~~~~~~~~~

Add a Dimension Space Point (DSP) Shine-Through; basically making all content available not just in the source (original) DSP,  but also in the target-DimensionSpacePoint.

NOTE: the Source Dimension Space Point must be a parent of the target Dimension Space Point.

Options Reference:

``from`` (array)
  Source Dimension Space Point as array. E.g. ["language" => "es", "country" => "es"]
``to`` (array)
  Target Dimension Space Point where the content has to shine through as array. E.g. ["language" => "es", "country" => "ar"]

AddNewProperty
~~~~~~~~~~~~~~

Add a new property with the given value.

Options Reference:

``newPropertyName`` (string)
  The name of the new property to be added.
``type`` (string)
  The type of the property (e.g. string, array, DateTime, ...)
``serializedValue`` (mixed)
  Property value to be set.

ChangeNodeType
~~~~~~~~~~~~~~

Change the node type.

Options Reference:

``newType`` (string)
  The new Node Type to use as a string.

``forceDeleteNonMatchingChildren`` (bool)
  This flag allows to enforce the migration. In case of child constraint conflicts the conflicting child nodes get deleted.

  Default is `false`.

ChangePropertyValue
~~~~~~~~~~~~~~~~~~~

Change the value of a given property.

This can apply two transformations:

- If newSerializedValue is set, the value will be set to this, with any occurrences of the ``currentValuePlaceholder`` replaced
  with the current value of the property.
- If search and replace are given, that replacement will be done on the value (after applying the ``newSerializedValue``, if set).

This would simply override the existing value:

.. code-block:: yaml

  transformations:
    -
      type: 'ChangePropertyValue'
      settings:
        property: 'title'
        newSerializedValue: 'a new value'

This would prefix the existing value:

.. code-block:: yaml

  transformations:
    -
      type: 'ChangePropertyValue'
      settings:
        property: 'title'
        newSerializedValue: 'this is a prefix to {current}'

This would prefix existing value and then apply search/replace on the result:

.. code-block:: yaml

  transformations:
    -
      type: 'ChangePropertyValue'
      settings:
        property: 'title'
        newSerializedValue: 'this is a prefix to {current}'
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
        newSerializedValue: 'this is a prefix to {__my_unique_placeholder}'
        currentValuePlaceholder: '__my_unique_placeholder'

Options Reference:

``property`` (string)
  The name of the property to change.
``newSerializedValue`` (string)
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

MoveDimensionSpacePoint
~~~~~~~~~~

Moves a dimension space point globally.

``from`` (array)
  Source Dimension Space Point as array. E.g. ["language" => "es", "country" => "es"]
``to`` (array)
  Target Dimension Space Point as array. E.g. ["language" => "es", "country" => "ar"]


RemoveNode
~~~~~~~~~~

Removes the node.

``overriddenDimensionSpacePoint`` (array)
  Dimension Space Point as array. E.g. ["country" => "ar"]

  This allows to remove nodes in a virtual specialization or shine-through dimension space points.

RemoveProperty
~~~~~~~~~~~~~~

Remove the property.

Options Reference:

``property`` (string)
  The name of the property to be removed.

RenameNodeAggregate
~~~~~~~~~~

Rename a node aggregate.

Hint: Why node aggregate, not node? The node aggregate contains all information, that are equal for a node over all dimensions. So the name of a node is stored in the node aggregate and not in each node anymore.

Options Reference:

``newNodeName`` (string)
  The new name for the node aggregate.

RenameProperty
~~~~~~~~~~~~~~

Rename a given property.

Options Reference:

``from`` (string)
  The name of the property to change.
``to`` (string)
  The new name for the property to change.


StripTagsOnProperty
~~~~~~~~~~~~~~~~~~~

Strip all tags on a given property.

Options Reference:

``property`` (string)
  The name of the property to work on.



Filters Reference
-----------------

DimensionSpacePoints
~~~~~~~~~~~~~~~

Filter nodes by origin dimension space point.

Options Reference:

``points`` (array)
  The array of dimension space point values to filter for.
``includeSpecializations`` (boolean)
  If set to `false` it checks for exact matches; but if set to `true`, also dimension space points "underneath" the given
  dimension space point are matched (specializations). Default is `false`.

NodeName
~~~~~~~~

Selects nodes with the given name.

Options Reference:

``nodeName`` (string)
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

PropertyValue
~~~~~~~~~~~~~~~~

Filter nodes having the given property with the corresponding value.

Options Reference:

``propertyName`` (string)
  The property name to filter for with the given property value.
``serializedValue`` (string)
  The property value to filter for.
