=======================
Selecting a Page Layout
=======================

TYPO3 Neos has a flexible way of choosing a layout, which can be selected in the backend.

First of all, the necessary layouts have to be configured inside `VendorName.VendorSite/Configuration/NodeTypes.yaml`::

    'TYPO3.Neos:Page':
      properties:
        layout:
          ui:
            inspector:
              editorOptions:
                values:
                  'default':
                    label: 'Default'
                  'landingPage':
                    label: 'Landing page'
        subpageLayout:
          ui:
            inspector:
              editorOptions:
                values:
                  'default':
                    label: 'Default'
                  'landingPage':
                    label: 'Landing page'

Here, the properties `layout` and `subpageLayout` are configured inside `TYPO3.Neos:Page`:

* `layout`: Changes the layout of the current page
* `subpageLayout`: Changes the layout of subpages if nothing else was chosen.



When all this is done we need to bind the layout to a rendering and this is done in TypoScript,
f.e. in VendorName.VendorSite/Resources/Private/TypoScripts/Library/Root.ts::

    page.body {
        // standard "Page" configuration
    }

    default < page

    landingPage < page
    landingPage.body {
        templatePath = 'resource://VendorName.VendorSite/Private/Templates/Page/LandingPage.html'
    }

If a page layout was chosen, that is the TypoScript object path where rendering starts.
For example, if the `landingPage` was chosen, a different template can be used.

The implementation internal of the layout rendering can be found in the file:

TYPO3.Neos/Resources/Private/TypoScript/DefaultTypoScript.ts2

The element `root.layout` is the one responsible for handling the layout. So when trying to
change the layout handling this is the TypoScript object to manipulate.