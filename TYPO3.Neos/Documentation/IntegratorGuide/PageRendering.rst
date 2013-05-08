.. _page-rendering:

================
Rendering A Page
================

This section shows how content is rendered on a page as a rough overview.

More precisely we show how to render a `Folder` node, as everything which happens
here works for all `Folder` nodes, and not just for `Page` nodes.

First, the requested URL is resolved to a Node of type `TYPO3.TYPO3CR:Folder`.
This happens by translating the URL path to a node path, and finding the node
with this path then.

When this node is found, the system searches for the *TypoScript* configuration
which is active for this node by traversing all the parent nodes and looking for
any attached TypoScript.

Then the node is passed straight away to TypoScript, which is the rendering mechanism.
TypoScript renders the node by traversing to sub-nodes and rendering them as well.
The arguments which are passed to TypoScript are stored inside the so-called
*context*, which contains all variables which are accessible by the TypoScript rendering
engine.

Internally, TypoScript then asks *Fluid* to render certain snippets of the page,
which can, in turn, ask TypoScript again. This can go back and forth multiple
times, even recursively.

The Page TypoScript Object and -Template
========================================

The rendering of a page by default starts at a `Case` matcher which will usually
select the TypoScript path `page`.  The minimally needed TypoScript for rendering
looks as follows::

	page = Page
	page.body.templatePath = 'resource://My.Package/Private/Templates/PageTemplate.html'

Here, the `Page` TypoScript object is assigned to the path `page`, telling the
system that the TypoScript object `Page` is responsible for further rendering.
`Page` expects one parameter to be set: The path of the Fluid template which
is rendered inside the `<body>` of the resulting HTML page.

If this is an empty file, the output shows how minimal Neos impacts the generated
markup::

	<!DOCTYPE html>
	<html version="HTML+RDFa 1.1"
		  xmlns="http://www.w3.org/1999/xhtml"
		  xmlns:typo3="http://www.typo3.org/ns/2012/Flow/Packages/Neos/Content/"
		  xmlns:xsd="http://www.w3.org/2001/XMLSchema#"
		  >
		<!--
			This website is powered by TYPO3 Neos, the next generation CMS, a free Open
			Source Enterprise Content Management System licensed under the GNU/GPL.

			TYPO3 Neos is based on Flow, a powerful PHP application framework licensed under the GNU/LGPL.

			More information and contribution opportunities at http://neos.typo3.org and http://flow.typo3.org
		-->
		<head>
			<base href="http://your.doma.in/" />
			<meta charset="UTF-8" />
			<script>
		try {
			with (window.location) {
				sessionStorage.setItem(
					'TYPO3.Neos.lastVisitedUri',
					[protocol, '//', host, pathname, (pathname.charAt(pathname.length - 1) === '/' ? 'home.html' : '')].join('')
				);
			}
		} catch(e) {}
	</script>
		</head>
		<body>
		</body>
	</html>

It becomes clear that Neos gives as much control over the markup as possible to the
integrator: No body markup, no styles, only little Javascript to record the last visited
URI (to redirect back to it after logging in). Except for the base URI and the charset
nothing related to the content is output by default.

If the template is filled with the following content::

	<h1>{title}</h1>

the body would contain a heading to output the title of the current page::

	<body>
		<h1>My first page</h1>
	</body>

Again, no added CSS classes, no wraps. Why `{title}` outputs the page title will be
covered in detail later.

Of course the current template is still quite boring; it does not show any content
or any menu. In order to change that, the Fluid template is adjusted as follows::

	{namespace ts=TYPO3\TypoScript\ViewHelpers}
	<ts:renderTypoScript path="parts/menu" />
	<h1>{title}</h1>
	<ts:renderTypoScript path="sections/main" />

Placeholders for the menu and the content have been added with the use of the
`renderTypoScript` ViewHelper. It defers rendering to TypoScript again, so the
TypoScript needs to be adjusted as well::

	page = Page
	page.body {
		templatePath = 'resource://My.Package/Private/Templates/PageTemplate.html'
		parts.menu = Menu
		sections.main = Section
		sections.main.nodePath = 'main'
	}

In the above TypoScript, a TypoScript object at `page.body.parts.menu` is defined
to be of type `Menu`. It is exactly this TypoScript object which is rendered, by
specifying its relative path inside `<ts:renderTypoScript path="parts/menu" />`.

Furthermore, the `Section` TypoScript object is used to render a TYPO3CR `Section`
node. Through the `nodePath` property, the name of the TYPO3CR `Section` node to
render is specified.

As a result, the web page now contains a menu and the contents of the main section.

The use of `section` and `parts` here is simply a convention, the names can be
chosen freely. In the example `sections` is used for anything that content is later
placed in but `parts` is for anything that is not *content* in the sense that it
will directly be edited in the content module of Neos.

Adjusting Menu Rendering
========================

Out of the box the `Menu` is rendered using a simple unsorted list. Using TypoScript
it is possible to change the rendered markup of `Menu`. Knowing how the `Menu` object
works internally helps with this and gives insight into all other TypoScripts objects
as well.

By specifying `page.body.parts.menu = Menu`, a `Menu` TypoScript object is
*instantiated*  at the TypoScript path `page.body.parts.menu`. `Menu` is defined
inside the core of TYPO3 Neos together with TYPO3 Neos.NodeTypes:

*TYPO3.Neos/Resources/Private/DefaultTypoScript/ImplementationClasses.ts2*

::

	prototype(TYPO3.Neos:Menu).@class = 'TYPO3\\Neos\\TypoScript\\MenuImplementation'

*TYPO3.Neos.NodeTypes/Resources/Private/TypoScript/Root.ts2*

::

	prototype(TYPO3.Neos.NodeTypes:Menu) < prototype(TYPO3.Neos:Menu)
	prototype(TYPO3.Neos.NodeTypes:Menu) {
		templatePath = 'resource://TYPO3.Neos.NodeTypes/Private/Templates/TypoScriptObjects/Menu.html'
		entryLevel = ${q(node).property('startLevel')}
		entryLevel << 1.toInteger()
		maximumLevels = ${q(node).property('maximumLevels')}
		maximumLevels << 1.toInteger()
		node = ${node}
	}

The above code defines the *prototype* of `Menu` with the `prototype(Menu)` syntax.
This prototype is the "blueprint" of all `Menu` objects which are instantiated.
All properties which are defined on the prototype (such as `@class` or `templatePath`)
are automatically active on all `Menu` *instances*, if they are not explicitly overridden.

One way to adjust the menu rendering is to override the `templatePath` property, which
points to a Fluid template. To achieve that, we have two possibilities.

First, the `templatePath` for the menu at `page.body.parts.menu` can be set::

	page.body.parts.menu.templatePath = 'resource://My.Package/Private/Templates/MyMenuTemplate.html'

This overrides the `templatePath` which was defined in `prototype(Menu)` for
this single menu.

Second, the `templatePath` inside the prototype of `Menu` itself can be changed::

	prototype(Menu).templatePath = 'resource://My.Package/Private/Templates/MyMenuTemplate.html'

In this case, the changed template path is used for *all menus* which do not override
the `templatePath` explicitly. Every time `prototype(...)` is used, this can be
understood as: "For *all* objects of type ..., define *something*"

After setting the path, changing the menu is simply a job of copying the default
`Menu` template into `MyMenuTemplate.html` and adjusting the markup as needed.

Adjusting Content Element Rendering
===================================

The rendering of content elements follows the same principle as shown for the `Menu`.
The default TypoScript is defined in the Neos.NodeTypes package and the content elements
all have default Fluid templates.

Combined with the possibility to define custom templates per instance or on the prototype
level, this already provides a lot of flexibility. Another possibility is to inherit from
the existing TypoScript and adjust as needed using TypoScript.
