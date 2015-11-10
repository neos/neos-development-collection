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
    Flow:
      Neos:
        navigateComponent:
          userInterface:
            nodeTree:
              loadingDepth: 2
