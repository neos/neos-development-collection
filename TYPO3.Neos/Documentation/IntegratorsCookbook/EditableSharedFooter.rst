========================================
Editing a shared footer across all pages
========================================

A shared footer in Neos works as follows:

* The homepage contains a collection of content elements
* The same collection is rendered on all other pages

This enables you to edit the footer on all pages.

To add the footer to the page you use the `ContentCollection` with a static node path.

TypoScript code::

	footer = TYPO3.Neos:ContentCollection {
  	nodePath = ${q(site).children('home').children('footer').property('_path')}
  	collection = ${q(site).children('home').children('footer').children()}
  }

Of course you have to update the selections in the code example if your
homepage doesn't have the nodename `home`.
