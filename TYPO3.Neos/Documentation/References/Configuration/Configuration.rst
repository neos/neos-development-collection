.. _`Configuration Reference`:

.. note::
  This is a documentation stub.

Configuration Reference
========================

Node tree ``loadingDepth``
~~~~~~~~~~~~~~~~~~~~~~~~~~

``loadingDepth`` defines the number of levels inside the node tree which shall be loaded eagerly, at start.
If you have lots of nodes you should maybe reduce this number of elements inside ``Settings.yaml``::

  TYPO3:
    Neos:
      userInterface:
        navigateComponent:
          nodeTree:
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
                baseNodeType: 'TYPO3.Neos:Document,!Acme.Com:SomeNodeTypeToIgnore'

.. note::
  The naming of the configuration (``presets``) keeps into account that the node tree should support multiple presets
  in the future.
