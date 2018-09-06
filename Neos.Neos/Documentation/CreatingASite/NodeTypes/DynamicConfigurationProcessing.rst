.. _dynamic-configuration-processing:

============================================
Dynamic Client-side Configuration Processing
============================================

.. note:: This API is rather low-level and still experimental, we might change
   some of the implementation details or compliment it with a more high-level API.

All configuration values that begin with ``ClientEval:`` are dynamically evaluated on
the client side. They are written in plain JavaScript (evaluated with ``eval``) and
have ``node`` variable in the scope pointing to the currently focused node, with all
transient inspector changes applied. For now it is only related to the nodetypes
label and inspector configuration (with the exception of the group and position), but
in the future may be extended to the other parts of the user interface.

The following data is available in the ``node`` variable:

``children``
  An array of all direct children of the node containing an object with the ``nodeType``
  of each child.

``depth``
  The depth of the node in the node tree.

``identifier``
  The identifier of the node.

``label``
  The label with which the node is displayed inside the Neos UI.

``name``
  The name of the node.

``nodeType``
  The Node Type of the node.

``properties``
  An object with key–value pairs of all properties of the node.

A few Practical Examples
========================

Hiding one property when the other one is not set
-------------------------------------------------

Here is an example how to hide the property ``borderColor`` if ``borderWidth`` is empty
by hiding it in the inspector:

.. code-block:: yaml

  'Some.Package:NodeType':
    properties:
      borderWidth:
        type: integer
        ui:
          inspector:
            group: 'style'
      borderColor:
        type: string
        ui:
          inspector:
            hidden: 'ClientEval:node.properties.borderWidth ? false : true'

Dependent SelectBoxes
---------------------

If you are using select box editors with *data sources* (see :ref:`data-sources` for more details) you can use
client-side processing to adjust ``dataSourceAdditionalData`` when properties are changed in the inspector. The
following example demonstrates this. It defines two properties (*serviceType* and *contractType*) where changes to the
first property cause the ``searchTerm`` on the second properties' data source to be updated. That in turn triggers
a refresh of the available options from the data source.

.. code-block:: yaml

  properties:
    serviceType:
      type: string
      ui:
        label: 'Service Type'
        inspector:
          group: product
          editor: 'Content/Inspector/Editors/SelectBoxEditor'
          editorOptions:
            allowEmpty: true
            placeholder: 'Service Type'
            dataSourceIdentifier: 'acme-servicetypes'
    contractType:
      type: string
      ui:
        label: 'Contract Type'
        inspector:
          group: product
          editor: 'Content/Inspector/Editors/SelectBoxEditor'
          editorOptions:
            allowEmpty: true
            placeholder: 'Contract Type'
            dataSourceIdentifier: 'acme-contracttypes'
            dataSourceAdditionalData:
              searchTerm: 'ClientEval:node.properties.serviceType'
