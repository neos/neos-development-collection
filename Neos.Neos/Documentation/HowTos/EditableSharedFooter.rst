========================================
Editing a shared footer across all pages
========================================

A shared footer in Neos works as follows:

* The homepage contains a collection of content elements
* The same collection is rendered on all other pages

This enables you to edit the footer on all pages.

To add the footer to the page you use the `ContentCollection` with a static node path.

To have the collection on the homepage you need to configure the childNodes structure
of the homepage. For this you create a homepage node type with for example
the following configuration in NodeTypes.yaml::

	'My.Package:HomePage':
	  superTypes:
	    'Neos.Neos.NodeTypes:Page': TRUE
	  ui:
	    label: 'Homepage'
	  childNodes:
	    footer:
	      type: 'Neos.Neos:ContentCollection'

.. note::

	If you run into the situation that the child nodes for your page are missing
	(for example if you manually updated the node type in the database) you might
	have to create the missing child nodes using::

		./flow node:repair --node-type Neos.Neos.NodeTypes:Page

TypoScript code::

	footer = Neos.Neos:ContentCollection {
		nodePath = ${q(site).find('footer').property('_path')}
		collection = ${q(site).children('footer').children()}
	}

Of course you have to update the selection in the example if your footer is
not stored on the site root, but for example on a page named 'my-page'. The
selection would then be: `${q(site).find('my-page').children('footer').children()}`.
