.. _node-creation-dialog:

==================================
Node Creation Dialog Configuration
==================================

When creating new nodes, you have the possibility to provide additional data that will be
passed to ``nodeCreationHandlers``.

Creation dialog supports most of the inspector editors, except of those that require
to show a secondary inspector view. See :ref:`property-editor-reference` for more details about
configuring inspector editors.

For example, this functionality is used in Neos to ask users for title before creating document nodes:

.. code-block:: yaml

  'Neos.Neos:Document':
    ui:
      group: 'general'
      creationDialog:
        elements:
          title:
            type: string
            ui:
              label: i18n
              editor: 'Neos.Neos/Inspector/Editors/TextFieldEditor'
            validation:
              'Neos.Neos/Validation/NotEmptyValidator': []
    options:
      nodeCreationHandlers:
        documentTitle:
          nodeCreationHandler: 'Neos\Neos\Ui\NodeCreationHandler\DocumentTitleNodeCreationHandler'

You may register multiple ``nodeCreationHandlers`` per nodetype. Each nodeCreationHandler must implement
``NodeCreationHandlerInterface``. It gets the newly created ``$node`` and the ``$data`` coming from
the creation dialog.

.. note:: elements of the creation dialog define an arbitrary set of data that will be passed to a
   nodeCreationHandler, they will not automatically set node properties in any way. To take action based
   on that data you would need to write a custom node creation handler or use a package that already provides
   such functionality, e.g. Flowpack.NodeTemplates (https://github.com/Flowpack/Flowpack.NodeTemplates).
