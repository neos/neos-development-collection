.. _page-rendering:

================
Rendering A Page
================

This section shows how content is rendered on a page as a rough overview.

More precisely we show how to render a ``Neos.Neos:Document`` node, as everything which happens
here works for all ``Document`` nodes, and not just for ``Page`` nodes.

First, the requested URL is resolved to a Node of type ``Neos.Neos:Document``.
This happens by translating the URL path to a node path, and finding the node
with this path then.

The node is passed straight away to TypoScript, which is the rendering mechanism.
TypoScript renders the node by traversing to sub-nodes and rendering them as well.
The arguments which are passed to TypoScript are stored inside the so-called
*context*, which contains all variables which are accessible by the TypoScript rendering
engine.

Internally, TypoScript then asks *Fluid* to render certain snippets of the page,
which can, in turn, ask TypoScript again. This can go back and forth multiple
times, even recursively.

The Page TypoScript Object and -Template
========================================

The rendering of a page by default starts at a ``Case`` matcher at the TypoScript path ``root``,
which will usually select the TypoScript path ``page``.  The minimally needed TypoScript for rendering
looks as follows::

	page = Page
	page.body.templatePath = 'resource://My.Package/Private/Templates/PageTemplate.html'

Here, the ``Page`` TypoScript object is assigned to the path ``page``, telling the
system that the TypoScript object ``Page`` is responsible for further rendering.
``Page`` expects one parameter to be set: The path of the Fluid template which
is rendered inside the ``<body>`` of the resulting HTML page.

If this is an empty file, the output shows how minimal Neos impacts the generated
markup::

	<!DOCTYPE html>
	<html version="HTML+RDFa 1.1"
	    xmlns="http://www.w3.org/1999/xhtml"
	    xmlns:typo3="http://www.typo3.org/ns/2012/Flow/Packages/Neos/Content/"
	    xmlns:xsd="http://www.w3.org/2001/XMLSchema#"
	    >
	  <!--
	    This website is powered by Neos, the next generation CMS, a free Open
	    Source Enterprise Content Management System licensed under the GNU/GPL.

	    Neos is based on Flow, a powerful PHP application framework licensed under the GNU/LGPL.

	    More information and contribution opportunities at http://neos.typo3.org and http://flow.typo3.org
	  -->
	  <head>
	    <meta charset="UTF-8" />
	  </head>
	  <body>
	    <script src="/_Resources/Static/Packages/Neos.Neos/JavaScript/LastVisitedNode.js" data-neos-node="a319a653-ef38-448d-9d19-0894299068aa"></script>
	  </body>
	</html>

It becomes clear that Neos gives as much control over the markup as possible to the
integrator: No body markup, no styles, only little Javascript to record the last visited
page (to redirect back to it after logging in). Except for the charset meta tag nothing
related to the content is output by default.

If the template is filled with the following content::

	<h1>{title}</h1>

the body would contain a heading to output the title of the current page::

	<body>
		<h1>My first page</h1>
	</body>

Again, no added CSS classes, no wraps. Why ``{title}`` outputs the page title will be
covered in detail later.

Of course the current template is still quite boring; it does not show any content
or any menu. In order to change that, the Fluid template is adjusted as follows::

	{namespace ts=TYPO3\TypoScript\ViewHelpers}
	{parts.menu -> f:format.raw()}
	<h1>{title}</h1>
	{content.main -> f:format.raw()}

Placeholders for the menu and the content have been added. Because the ``parts.menu`` and
``content.main`` refer to a rendered TypoScript path, the output needs to be passed through
the ``f:format.raw()`` ViewHelper. The TypoScript needs to be adjusted as well::

	page = Page
	page.body {
		templatePath = 'resource://My.Package/Private/Templates/PageTemplate.html'

		parts.menu = Menu
		content.main = PrimaryContent {
			nodePath = 'main'
		}
	}

In the above TypoScript, a TypoScript object at ``page.body.parts.menu`` is defined
to be of type ``Menu``. It is exactly this TypoScript object which is rendered, by
specifying its relative path inside ``{parts.menu -> f:format.raw()}``.

Furthermore, the ``PrimaryContent`` TypoScript object is used to render a TYPO3CR
``ContentCollection`` node. Through the ``nodePath`` property, the name of the TYPO3CR
``ContentCollection`` node to render is specified.

As a result, the web page now contains a menu and the contents of the main content
collection.

The use of ``content`` and ``parts`` here is simply a convention, the names can be
chosen freely. In the example ``content`` is used for anything that content is later
placed in but ``parts`` is for anything that is not *content* in the sense that it
will directly be edited in the content module of Neos.

Further Reading
===============

Details on how TypoScript works and can be used can be found in the section :ref:`inside-typoscript`.
:ref:`adjusting-output` shows how page, menu and content markup can be adjusted freely.
