=======================
Selecting a Page Layout
=======================

Neos has a flexible way of choosing a layout which can be selected in the backend.

.. node::

    The layout mechanism is implemented in the package Neos.NodeTypes wich has to be installed.

First of all, the necessary layouts have to be configured inside `VendorName.VendorSite/Configuration/NodeTypes.yaml`::

    'Neos.NodeTypes:Page':
      properties:
        layout:
          ui:
            inspector:
              group: layout
              editorOptions:
                values:
                  'default':
                    label: 'Default'
                  'landingPage':
                    label: 'Landing page'
        subpageLayout:
          ui:
            inspector:
              group: layout
              editorOptions:
                values:
                  'default':
                    label: 'Default'
                  'landingPage':
                    label: 'Landing page'

Here, the properties `layout` and `subpageLayout` are configured inside `Neos.NodeTypes:Page`:

* `layout`: Changes the layout of the current page
* `subpageLayout`: Changes the layout of subpages if nothing else was chosen.

.. note::

    Notice that the group is set for both properties as well, because they're hidden by default.


When all this is done we need to bind the layout to a rendering and this is done in Fusion,
f.e. in VendorName.VendorSite/Resources/Private/Fusion/Root.ts::

    page.body {
        // standard "Page" configuration
    }

    default < page

    landingPage < page
    landingPage.body {
        templatePath = 'resource://VendorName.VendorSite/Private/Templates/Page/LandingPage.html'
    }

If a page layout was chosen, that is the Fusion object path where rendering starts.
For example, if the `landingPage` was chosen, a different template can be used.

The implementation internal of the layout rendering can be found in the file:

Neos.NodeTypes/Resources/Private/Fusion/DefaultFusion.fusion

The element `root.layout` is the one responsible for handling the layout. So when trying to
change the layout handling this is the Fusion object to manipulate.
