================
Rendering A Page
================

This section shows how content is rendered on a page as a rough overview.

.. note::
   More correctly we should have said that we show how to render a `Folder`
   node, as everything which happens here works for all `Folder` nodes, and not
   just for `Page` nodes.

First, the requested URL is resolved to a Node of type `TYPO3.TYPO3CR:Folder`.
This happens by translating the URL path to a node path, and finding the node
with this path then.

When this node is found, the system searches for the *TypoScript* configuration
which is active for this node by traversing all the parent nodes and looking for
any attached TypoScript.

Then, the node is passed straight away to TypoScript, which is our rendering machine.
TypoScript then renders the node by traversing to sub-nodes and rendering them as
well. The arguments which are passed to TypoScript are stored inside the so-called
*context*, which contains all variables which are accessible by the TypoScript rendering
engine.

Internally, TypoScript then asks *Fluid* to render certain snippets of the page,
which can, in turn, ask TypoScript again. This can go back and forth multiple
times, even recursively.

The Page TypoScript Object and -Template
========================================

.. TODO: make TS path "page" configurable: Introduce a "root" TS path of type "Case" which redirects to "page" path by default.
.. this enables to create f.e. an "RSS View" which is controlled by the Blog package.

The rendering of a page starts, by convention, at the TypoScript path `page`.
The minimally needed TypoScript for rendering looks as follows::

	page = Page
	page.body.templatePath = 'resource://My.Package/Private/Templates/PageTemplate.html'

Here, we assign the `Page` TypoScript object to the path `page`, telling the
system that the TypoScript object `Page` is responsible for further rendering.
`Page` expects one parameter to be set: The Fluid template path of the template
which is rendered inside the `<body>` of the resulting HTML page.

The template could f.e. contain the following contents::

	<h1>{title}</h1>

	Hello World!

This would output the title of the current page. You do not need to understand yet
why `{title}` outputs the page title, we will cover that in detail later.

Of course the current template is still quite boring; as we do not show any content
or any menu. In order to change that, we need to adjust our Fluid template as
follows::

	{namespace ts=\TYPO3\TypoScript\ViewHelpers}
	<div class="menu">
	  <ts:renderTypoScript path="parts/menu" />
	</div>
	<h1>{title}</h1>
	<ts:renderTypoScript path="sections/main" />

.. TODO: rename "renderTypoScript" VH to "render"
.. TODO: should the "renderTypoScript" VH convert the path "." to "/"?

You see that we have added placeholders for the menu and the content with the
`<ts:renderTypoScript>` ViewHelper. These placeholders are rendered by TypoScript
again, so we need to adjust our TypoScript as well::

	page = Page
	page.body {
	  templatePath = 'resource://My.Package/Private/Templates/PageTemplate.html'
	  parts.menu = Menu
	  sections.main = Section
	  sections.main.nodePath = 'main'
	}

In the above TypoScript, we have defined a TypoScript object at `page.body.parts.menu`
to be of type `Menu`. It is exactly this TypoScript object which is rendered, by
specifying its relative path inside `<ts:renderTypoScript path="parts/menu" />`.

Furthermore, we use the `Section` TypoScript object to render a TYPO3CR `Section`
node. Through the `nodePath` property, we specify the name of the TYPO3CR `Section`
node which we want to render.

As a result, our web page now contains a menu and the contents of the main section.

.. TODO: find different names for "Section". Currently we have:
.. - Fluid Sections as parts of bigger templates
.. - TYPO3CR Sections as collections of content
.. - TypoScript section elements -- related to TYPO3CR sections

.. TODO: explain the (somewhat arbitrary) distinction between parts and sections.
.. is that even best practice?

Adjusting Menu Rendering
========================

Currently, the `Menu` is rendered using a simple unsorted list. Now, let's say
we want to change the rendered markup of `Menu`. We'll not only explain the needed
changes, but also show how the `Menu` object (along with all other TypoScript
objects) works internally.

By specifying `page.body.parts.menu = Menu`, we *instanciate* the `Menu` TypoScript
object at the TypoScript path `page.body.parts.menu`. Now, let's look at the
definition of the `Menu`, which is defined inside the core of TYPO3 Neos
(inside the file `TYPO3.Neos.ContentTypes/Resources/Private/TypoScript/Root.ts2`)::

	prototype(Menu) {
		@class = 'TYPO3\\Neos\\TypoScript\\MenuImplementation'
		templatePath = 'resource://TYPO3.Neos.ContentTypes/Private/Templates/TypoScriptObjects/Menu.html'
		node = ${node}
		// ... there are some more properties defined as well, but these are not
		// ... so relevant for us.
	}

The above code defines the *prototype* of `Menu` with the `prototype(Menu)` syntax.
This prototype is the "blueprint" of all `Menu` objects which are instanciated.
All properties which are defined on the prototype (such as `@class` or `templatePath`)
are automatically active on all `Menu` *instances*, if they are not explicitely overridden.

Now, what do we need to do in order to adjust the menu rendering? The easiest way
of adjustment is to override the `templatePath` property, which points to a Fluid
template. To archive that, we have several possibilities.

First, we can set the `templatePath` for our menu at `page.body.parts.menu`::

	page.body.parts.menu.templatePath = 'resource://My.Package/Private/Templates/MyMenuTemplate.html'

This overrides the `templatePath` which was defined in `prototype(Menu)` for
this single menu.

Second, we could also update the `templatePath` inside the prototype of `Menu`
itself::

	prototype(Menu).templatePath = 'resource://My.Package/Private/Templates/MyMenuTemplate.html'

In this case, we changed the template paths for *all menus* which do not override
the `templatePath` explicitely. Everytime `prototype(...)` is used, this can be
understood as: "For all objects of type ..., I want to define *something*"

.. TODO: remove <typo3:aloha.* VHs; and also *.notEditable VHs; as they are not needed anymore

Now, adjusting the menu is simply a job of copying the default `Menu` template into
`MyMenuTemplate.html` and adjusting the markup as needed.
