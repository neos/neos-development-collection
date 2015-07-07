=======================
Selecting a Page Layout
=======================

Neos has a flexible way of choosing a layout, which can be selected in the backend.

First of all, the necessary layouts have to be configured inside `VendorName.VendorSite/Configuration/NodeTypes.yaml`::

    'TYPO3.Neos.NodeTypes:Page':
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

Here, the properties `layout` and `subpageLayout` are configured inside `TYPO3.Neos:Page`:

* `layout`: Changes the layout of the current page
* `subpageLayout`: Changes the layout of subpages if nothing else was chosen.

.. note::

	Notice that the group is set for both properties as well, because they're hidden by default.


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

Select Template based on NodeType
=================================

It is also possible to select the page rendering configuration based on the node type of the
page. Let's say you have a node type named `VendorName.VendorSite:Employee` which has `TYPO3.Neos.NodeTypes:Page`
as a supertype. This node type is used for displaying a personal page of employees working in
your company. This page will have a different structure compared to your basic page.

The right approach would be to create a TypoScript prototype for your default page and employee page like::

	prototype(VendorName.VendorSite:Page) < prototype(TYPO3.Neos:Page) {
		body.templatePath = 'resource//VendorName.VendorSite/Private/Templates/Page/Default.html'
		# Your further page configuration here
	}

	prototype(VendorName.VendorSite:EmployeePage) < prototype(VendorName.VendorSite:Page) {
		body.templatePath = 'resource//VendorName.VendorSite/Private/Templates/Page/Employee.html'
		# Your further employee page configuration here
	}

But now how to link this TypoScript path to your node type? For this we can have a look at the
TypoScript `root` path. This `root` path is a `TYPO3.TypoScript:Case` object, which will render
the `page` path by default. But you can add your own conditions to render a different path.

In our case we will add a condition on the first position of the condition::

	root.employeePage {
		condition = ${q(node).is('[instanceof VendorName.VendorSite:Employee]')}
		renderPath = '/employeePage'
	}

	page = VendorName.VendorSite:Page
	employeePage = VendorName.VendorSite:EmployeePage

This will now render the `employeePage` TypoScript path if a page of type `VendorName.VendorSite:Employee`
is rendered on your website.