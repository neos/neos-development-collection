========================================
Editing a shared footer across all pages
========================================

A shared footer in Neos works as follows:

* The homepage contains a collection of content elements
* The same collection is rendered on all other pages

This enables you to edit the footer on all pages.

To create a static footer you have to create 2 prototypes:

TypoScript code::

	prototype(My.Package:StaticFooterContainer) < prototype(TYPO3.Neos:ContentCollection) {
		nodePath = 'footer'
	}
	prototype(My.Package:StaticFooter) < prototype(TYPO3.Neos:ContentCollection) {
		nodePath = ${q(site).children('home').children('footer').property('_path')}
		collection = ${q(site).children('home').children('footer').children()}
	}

To add the footer to the page you use the `Case` object which
has a condition to check of the current page equals the homepage.

TypoScript code::

	footer = TYPO3.TypoScript:Case {
		onHomePage {
			condition = ${node.name == 'home'}
			type = 'My.Package:StaticFooterContainer'
		}
		default {
			type = 'My.Package:StaticFooter'
			condition = ${true}
			@position = 'end'
		}
	}

Of course you have to update the selections in the code example if your
homepage doesn't have the nodename `home`.
