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
inspector configuration, but in the future may be extended to the other parts of
the user interface.


A few Practical Examples
========================

Hiding one property when other is not set
-----------------------------------------

Here is an example how to hide the property ``border-color`` if ``border-width`` is empty
by changing its group name to a non-existant value:

.. code-block:: yaml

  'Some.Package:NodeType':
    properties:
      border-width:
        type: integer
        ui:
          inspector:
            group: 'style'
      border-color:
        type: string
        ui:
          inspector:
            group: 'ClientEval:node.properties["border-width"] ? "style" : "invalid-group"'

Dependant SelectBoxes
---------------------

If you are using select box editors with *data sources* (see :ref:`data-sources` for more details) you can use
client-side processing to adjust ``dataSourceAdditionalData`` when properties are changed in the inspector. The
following example shows this. It defines two properties (*serviceType* and *contractType*) where changes to the
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
