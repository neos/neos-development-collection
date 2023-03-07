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


Login Form
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The login form can be customized by configuring a different background image or logo.
You can even use custom CSS to change the look and feel of the login form.

To achieve this, you are able to change several values in your ``Settings.yaml``:
By default we use the following values:

.. code-block:: yaml

  Neos:
    Neos:
      userInterface:
        backendLoginForm:
            backgroundImage: 'resource://Neos.Neos/Public/Images/Login/Wallpaper.webp'
            logoImage: 'resource://Neos.Neos/Public/Images/Login/Logo.svg'
            stylesheets:
                'Neos.Neos:DefaultStyles': 'resource://Neos.Neos/Public/Styles/Login.css'


``backgroundImage`` defines the background image of the login form on the login page.
The image can be a resource or a path to an image.

``logoImage`` defines the logo image of the login form on the login page.
The image can be a resource or a path to an image.

``stylesheets`` defines the stylesheets which are loaded on the login page.