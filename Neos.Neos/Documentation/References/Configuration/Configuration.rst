.. _`Configuration Reference`:

.. note::
  This is a documentation stub.

Configuration Reference
========================

Navigation tree ``loadingDepth``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

``loadingDepth`` defines the number of levels inside the node tree which shall be loaded eagerly, at start.
A similar setting is available for the structure tree.

If you have lots of nodes you can reduce the number of levels inside ``Settings.yaml`` to speed up page loading::

  TYPO3:
    Neos:
      userInterface:
        navigateComponent:
          nodeTree:
            loadingDepth: 2
          structureTree:
            loadingDepth: 2

Node tree base node type
~~~~~~~~~~~~~~~~~~~~~~~~

Allows configuring the baseNodeType used in the node tree.

This example shows how to exclude one specific node type (and it's children) from the tree:

.. code-block:: yaml

  TYPO3:
    Neos:
      userInterface:
        navigateComponent:
          nodeTree:
            presets:
              default:
                baseNodeType: 'Neos.Neos:Document,!Acme.Com:SomeNodeTypeToIgnore'

.. note::
  The naming of the configuration (``presets``) keeps into account that the node tree should support multiple presets
  in the future.
