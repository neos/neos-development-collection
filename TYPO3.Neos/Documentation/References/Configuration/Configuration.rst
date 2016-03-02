.. _`Configuration Reference`:

.. note::
  This is a documentation stub.

Configuration Reference
========================

Navigation tree ``loadingDepth``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

``loadingDepth`` defines the number of levels inside the node tree which shall be loaded eagerly, at start.
A similar setting is available for the structure tree.

If you have lots of nodes can reduce the number of levels inside ``Settings.yaml`` to speed up page loading::

  TYPO3:
    Neos:
      userInterface:
        navigateComponent:
          nodeTree:
            loadingDepth: 2
          structureTree:
            loadingDepth: 2
