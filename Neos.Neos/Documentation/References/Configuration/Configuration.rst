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

  Neos:
    Neos:
      userInterface:
        navigateComponent:
          nodeTree:
            loadingDepth: 2
          structureTree:
            loadingDepth: 2

Node tree presets
~~~~~~~~~~~~~~~~~

By default all node types that extend ``Neos.Neos:Document`` appear in the ``Node tree filter``
allowing the editor to only show nodes of the selected type in the tree.

The default ``baseNodeType`` can be changed in order to hide nodes from the tree by default.

This example shows how to exclude one specific node type (and it's children) from the tree:

.. code-block:: yaml

  Neos:
    Neos:
      userInterface:
        navigateComponent:
          nodeTree:
            presets:
              'default':
                baseNodeType: 'Neos.Neos:Document,!Acme.Com:SomeNodeTypeToIgnore'

In addition to the ``default`` preset, additional presets can be configured such as:

.. code-block:: yaml

  Neos:
    Neos:
      userInterface:
        navigateComponent:
          nodeTree:
            presets:
              'default':
                baseNodeType: 'Neos.Neos:Document,!Acme.Com:Mixin.HideInBackendByDefault'
              'legalPages':
                ui:
                  label: 'Legal pages'
                  icon: 'icon-gavel'
                baseNodeType: 'Acme.Com:Document.Imprint,Acme.Com:Document.Terms'
              'landingPages':
                ui:
                  label: 'Landing pages'
                  icon: 'icon-bullseye'
                baseNodeType: 'Acme.Com:Mixin.LandingPage'

If at least one custom preset is defined, instead of the list of all node types the filter will
display the configured presets.
