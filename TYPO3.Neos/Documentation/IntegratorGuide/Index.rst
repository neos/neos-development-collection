===========================
TYPO3 Neos Integrator Guide
===========================

by Sebastian Kurfürst.

This guide explains how to implement websites with TYPO3 Neos. It specifically
covers the structuring of content using the *TYPO3 Content Repository (TYPO3CR)*,
and how the content is rendered using *TypoScript* and *Fluid*.

.. warning:: This guide is still work-in-progress. Its contents might still be incorrect or not yet consistent.

The TYPO3 Content Structure
===========================

Before we can understand how content is rendered, we have to see how it is structured
and organized. These basics are explained in this section.

Nodes inside the TYPO3 Content Repository
-----------------------------------------

All content in Neos is stored not inside tables of a relational database, but
inside a *tree-based* structure: the so-called TYPO3 Content Repository.

To a certain extent, it is comparable to files in a file-system: They are also
structured as a tree, and are identified uniquely by the complete path towards
the file.

.. note:: Internally, the TYPO3CR currently stores the nodes inside database
   tables as well, but you do not need to worry about that as you'll never deal
   with the database directly. This high-level abstraction helps to decouple
   the data modelling layer from the data persistence layer.

Each element in this tree is called a *Node*, and is structured as follows:

* It has a *node name* which identifies the node, in the same way as a file or
  folder name identifies an element in your local file system.
* It has a *node type* which determines which properties a node has. Think of
  it as the type of a file in your file system.
* Furthermore, it has *properties* which store the actual data of the node.
  The *node type* determines which properties exist for a node. As an example,
  a `text` node might have a `headline` and a `text` property.
* Of course, nodes may have *sub nodes* underneath them.

If we imagine a classical website with a hierarchical menu structure, then each
of the pages is represented by a TYPO3CR Node of type `Folder`. However, not only
the pages themselves are represented as tree: Imagine a page has two columns,
with different content elements inside each of them. The columns are stored as
Nodes of type `Section`, and they contain nodes of type `Text`, `Image`, or
whatever structure is needed. This nesting can be done indefinitely: Inside
a `Section`, there could be another three-column element which again contains
`section` elements with arbitrary content inside.

.. admonition:: Comparison to TYPO3 CMS

	In TYPO3 CMS, the *page tree* is the central data structure, and the content
	of a page is stored in a more-or-less flat manner in a separate database table.

	Because this was too limited for complex content, TemplaVoila was invented.
	It allows to create an arbitrary nesting of content elements, but is still
	plugged into the classical table-based architecture.

	Basically, TYPO3 Neos generalizes the tree-based concept found in TYPO3 CMS
	and TemplaVoila and implements it in a consistent manner, where we do not
	have to distinguish between pages and other content.

Content Type Definition
-----------------------

Each TYPO3CR Node (we'll just call it Node in the remaining text) has a specific
*content type* (which is the same as the *node type*, and we'll use the two terms
as synonyms).

.. TODO: DECIDE ON either Content Type or Node Type (in terms of naming)

Content Types are defined in `Configuration/Settings.yaml` underneath
`TYPO3.TYPO3CR.contentTypes`.

.. note:: TODO: it could be that the content types will be defined in an extra
   `ContentTypes.yaml` lateron...

Each content type can have *one or multiple parent types*. If these are specified,
all properties and settings of the parent types are inherited.

A content type can look as follows::

	'My.Package:SpecialHeadline':
	  superTypes: ['TYPO3.Neos.ContentTypes:ContentObject']
	  label: 'Special Headline'
	  group: 'General'
	  properties:
	    headline:
	      default: 'My Headline Default'
	  inlineEditableProperties: ['headline']

.. TODO: think about structure of these options...

The following options are allowed:

* `superTypes`: array of parent content types which are inherited from

* `label`: The human-readable label of the content type

* `icon`: (optional) The path to the icon of the content element on dark background

* `darkIcon`: (optional) The path to the icon of the content element on light background

* `group`: (optional) Name of the group in which the content type belongs. It can only
  be created through the user interface if `group` is defined and it is valid.

  All valid groups are listed inside `TYPO3.Neos.contentTypeGroups`

* `nonEditableOverlay`: (boolean, optional). If TRUE, a non-editable overlay is shown
  in the backend of Neos.

* `properties`: list of properties for this content type. For each property,
  the following settings can be done:

	* `type`: type of the property. One of:
		* `boolean`
		* `string`
		* `date`
		* `enum`
		* `TYPO3\Media\Domain\Model\ImageVariant`
		* additional types can be used, as long as they are defined inside
		  `TYPO3.Neos.userInterface` (TODO: bad name!) or specify a JavaScript
		  class inside `userInterface.class`

	* `label`: Label of the property

	* `default`: (optional) Default value. If a new node is created of this type, then it
	  is initialized with this value.

	* `group`: (optional) Name of the *property group* this property is categorized
	  into in the content editing user interface. If none is given, the property
	  is not editable through the property inspector of the user interface.

	  The value here must reference a configured property group in the `groups`
	  configuration element of this content element.

	* `priority`: (optional, integer) controls the sorting of the property inside the given
	  `group`. The highest priority is rendered on top (TODO: inconsistent with
	  @position in TypoScript...). Only makes sense if `group` is specified.

	* `reloadOnChange`: (optional) If TRUE, the whole content element needs to
	  be re-rendered on the server side if the value changes. This only works
	  for properties which are displayed inside the property inspector, i.e. for
	  properties which have a `group` set.

	* `options`: (optional) Specify type-specific further options for the given
	  content type. Currently only used if `type=enum`.

	* `userInterface`: (optional) Contains user interface related properties of
	  the property; used for the property inspector. Currently the only supported
	  sub-property is `class`, where an `Ember.View` can be specified which is rendered
	  inside the property inspector.

* `groups`: list of groups inside the *property inspector* for this content type.
  Each group has the following settings:

	* `label`: Displayed label of the group
	* `priority`: (integer) Controls the sorting of the groups. The highest-priority
	  group is rendered on top (TODO: inconsistent with @position in TypoScript)

* `inlineEditableProperties`: Array of property names which are editable directly
  on the page through Create.JS / Aloha. (TODO: check that all properties editable
  through Aloha / Create.JS ARE indeed marked as inlineEditableProperties; and the
  other way around as well)

* `structure`: When the given content type is created, the subnodes listed underneath
  here are automatically created. Example::

	structure:
	  column0:
	    type: 'TYPO3.Neos.ContentTypes:Section'

Here is an example content type::

	TYPO3:
	  TYPO3CR:
	    contentTypes:
	      'My.Package:SpecialImageWithTitle':
	        label: 'Image'
	        superTypes: ['TYPO3.Neos.ContentTypes:ContentObject']
	        group: 'General'
	        icon: 'Images/Icons/White/picture_icon-16.png'
	        darkIcon: 'Images/Icons/Black/picture_icon-16.png'
	        properties:
	          # the "title" property is not specified here, but is
	          # inherited from TYPO3.Neos.ContentTypes:ContentObject
	          image:
	            type: TYPO3\Media\Domain\Model\ImageVariant
	            label: 'Image'
	            group: 'image'
	            reloadOnChange: true
	          imagePosition:
	            type: enum
	            label: 'Image Position'
	            group: 'image'
	            default: 'left'
	            options:
	              values:
	                'left':
	                  label: 'Left Align'
	                'right':
	                  label: 'Right Align'
	        groups:
	          image:
	            label: 'Image'
	            priority: 5
	        inlineEditableProperties: ['title']

.. note:: Currently it is not possible to validate these content types automatically,
   but that is definitely a TODO.


Predefined Content Types
------------------------
TYPO3 Neos is shipped with a bunch of content types. It is helpful to know some of
them, as they can be useful elements to extend, and Neos depends on some of them
for proper behavior.

All default content types in a Neos installation are defined inside the
`TYPO3.Neos.ContentTypes` package.

In this section, we will spell out content types by their abbreviated name if they
are located inside the package `TYPO3.Neos.ContentTypes` to increase legibility:
Instead of writing `TYPO3.Neos.ContentTypes:AbstractNode` we will write `AbstractNode`.
However, we will spell out `TYPO3.TYPO3CR:Folder`.

AbstractNode
~~~~~~~~~~~~

`AbstractNode` is an (more or less internal) base type which
should be extended by all content types which are used in the context of TYPO3 Neos.
It defines the visibility settings (hidden, hidden before/after date) and makes sure
the user interface is able to delete nodes. In almost all cases, you will never extend
this type directly.

Folder
~~~~~~

An important distinction is between nodes which look and behave like pages
and "normal content" such as text, which is rendered inside a page. Nodes which
behave like pages are called *Folder Nodes* in Neos. This means they have a unique,
externally visible URL by which they can be rendered.

Folder nodes all inherit from `TYPO3.TYPO3CR:Folder`. However, instead of extending
this type directly, you will often extend `Folder`, as this one inerhits additionally
from `AbstractNode`.

The standard *page* in Neos is implemented by `Page` which directly extends from `Folder`.

It is supported to create own types extending from `Folder`.

Sections and ContentObjects
~~~~~~~~~~~~~~~~~~~~~~~~~~~

All content which does not behave like pages, but which lives inside them, is
implemented by two different content types:

First, there is the `Section` type: A `Section` has a structural purpose. It usually
does not contain any properties itself, but it contains an ordered list of child
nodes which are rendered inside.

Currently, `Section` should not be extended by custom types.

.. TODO: check why that does not work, can we fix that?

Second, the content type for all standard elements (such as text, image,
youtube, ...) is `ContentObject`. This is -- by far -- the most-extended
content type. It only defines a (visible or invisible) `title` property
by which the content can be identified.

Extending `ContentObject` is supported and encouraged.

.. TODO: check how we can transform one content type into another (f.e. 2col
.. into 3col) What happens with superfluous structure etc then?

.. TODO: should we rename "Section" to "Container"?
.. TODO: Introduce "MainContainer" and "SecondaryContainer" to make hooking into the main content area of a page easier for plugins?

Rendering A Page
================

This section shows how content is rendered on a page as a rough overview.

.. note:: More correctly we should have said that we show how to render a `Folder`
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
----------------------------------------

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
------------------------

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


Creating Custom Content Types
-----------------------------

In TYPO3 Neos, it is very easy to create custom content types. In fact, while Neos
ships with some commonly used, predefined content types, it is easily possible to
completely replace them.

Defining new content types is usually a three-step process:

#. Define the *TYPO3CR Content Type*, listing the properties and types of the node.

#. Define a *TypoScript object* which is responsible for rendering this content type.
   Usually, this is a wrapper for a Fluid Template which then defines the rendered
   markup.

#. Add a *Fluid Template* which contains the markup being rendered

Let's say you want to create a new content type `My.Package:YouTube` which needs
the YouTube URL and then renders the video player.

First, you need to create the *TYPO3CR Content Type* in `Settings.yaml`::

	TYPO3:
	  TYPO3CR:
	    contentTypes:
	      'My.Package:YouTube':
	        superTypes: ['TYPO3.Neos.ContentTypes:ContentObject']
	        group: 'General'
	        label: 'YouTube Video'
	        properties:
	          videoUrl:
	            label: 'Video URL'
	            type: string

Then, we have to define the TypoScript rendering for this content type. By convention,
a TypoScript object with the same name as the content type is used for rendering; thus
we need to define a TypoScript object `My.Package:YouTube` which takes care of rendering::

	prototype(My.Package:YouTube) < prototype(Template) {
	  templatePath = 'resource://My.Package/Private/Templates/YouTube.html'
	  videoUrl = ${q(node).property('videoUrl')}
	  width = '640'
	  height = '360'
	}

In the first line, we define a new TypoScript object prototype with name `My.Package:YouTube`,
and inherit from the pre-defined `Template` TypoScript object which provides rendering through
Fluid.

We then set the `templatePath` property of the `YouTube` TypoScript object to point to the
Fluid template we want to use for rendering. All (other) properties which are set on the
`Template` TypoScript object are directly made available inside Fluid as variables -- and
because the `YouTube` TypoScript object is extended from the `Template` TypoScript object, this
rule also applies there.

Thus, the last line defines a `videoUrl` variable being available inside Fluid, which is
set to the value `${q(node).property('videoUrl')}`. This is a so-called *Eel Expression*,
because it has the form `${....}`. So let's dissect the expression `q(node).property('videoUrl')`
now:

* The syntax of Eel is a subset of JavaScript, so if you roughly know JavaScript, it should
  feel very familiar to you. Essentially, everything you can write as a single expression in
  JavaScript can be written inside Eel as well.

* The q() function wraps its argument, in this case the TYPO3CR Node which is currently rendered,
  into *FlowQuery*. FlowQuery is comparable to jQuery: It is a selector language which allows to
  traverse nodes and other objects with an effective domain-specific language.

* FlowQuery defines certain *operations*, for example we're using the `property(...)` operation
  here to access the property of a node.

To sum it up: The expression `${q(node).property('videoUrl')}` is an Eel expression, in which
FlowQuery is called to return the property `videoUrl` of the current node.

Finally, creating the YouTube content element is as easy as filling the `YouTube.html` Fluid
template, f.e. with the following content::

	{namespace neos=TYPO3\Neos\ViewHelpers}
	<neos:contentElement node="{node}">
	  <iframe width="{width}" height="{height}" src="{videoUrl}" frameborder="0" allowfullscreen></iframe>
	</neos:contentElement>

You see that we use the `{videoUrl}` which has been defined in TypoScript, and output it inside
the template as we need it.

.. admonition:: Why is the indirection through TypoScript needed?

	If you paid close attention to the above example, you saw that the `videoUrl` property of the
	*Node* is not directly rendered inside the Fluid template. Instead we use *TypoScript* to pass
	the `videoUrl` from the *Node* into the Fluid template.

	While this indirection might look superfluous at first sight, it has important benefits:

	* First, the Fluid Template does not need to know anything about *Nodes*. It just needs to know
	  that it outputs a certain property, but not where it came from.

	* Because the rendering is decoupled from the data storage this way, we can easily instanciate the
	  TypoScript object directly, manually setting a `videoUrl`::

		page.body.parts.teaserVideo = My.Package:YouTube {
		  videoUrl = 'http://youtube.com/.....'
		}

	* If a property needs to be modified *just slightly*, we can use a *processor* for declaratively
	  modifying this property in TypoScript; not even touching the Fluid template. This is helpful for
	  smaller adjustments to foreign packages.

The only thing to be aware of inside the Fluid templates is the proper wrapping of the whole content
element with the `<neos:contentElement>` ViewHelper, which is needed to make the content element
selectable inside the Neos backend.

.. TODO: we could use a processor instead of <neos:contentElement>. Is that better or not?
.. TODO: processor ordering: maybe we can also use @position syntax here?? Is it consistent with ordering in TypoScript Collections?

.. TODO: naming of the above neos:contentElement viewhelper. ContentElement vs ContentObject (in TYPO3CR Content Type definition) <-- naming

Creating Nested Content Types
-----------------------------

In case you want to create content types which do not only contain simple properties, but arbitrary
sub-nodes, the process is roughly as above. To demonstrate this, we will create a `Video Grid` content
element which can contain two texts and two videos, and layouts them next to each other.

#. First, we create a TYPO3CR Content Type definition. Especially helpful is the `structure` option
   in the schema, as it allows to create sub-nodes on object creation. In our example below, we will
   directly create the two video and text elements on object creation::

	TYPO3:
	  TYPO3CR:
	    contentTypes:
	      'My.Package:VideoGrid':
	        superTypes: ['TYPO3.Neos.ContentTypes:ContentObject']
	        group: 'Structure'
	        label: 'Video Grid'
	        structure:
	          video0:
	            type: 'My.Package:Video'
	          video1:
	            type: 'My.Package:Video'
	          text0:
	            type: 'Text'
	          text1:
	            type: 'Text'

#. Second, we create the TypoScript as needed::

	prototype(My.Package:VideoGrid) < prototype(Template) {
	  templatePath = 'resource://My.Package/Private/Templates/VideoGrid.html'

	  videoRenderer = My.Package:YouTube

	  textRenderer = Text

	  video0 = ${q(node).children('video0')}
	  video1 = ${q(node).children('video1')}
	  text0 = ${q(node).children('text0')}
	  text1 = ${q(node).children('text1')}
	}

   Instead of using Eel and FlowQuery to assign variables to the Fluid template, we're now *instanciating
   additional TypoScript objects* responsible for the YouTube and the Text rendering. Furthermore, we pass
   the video and text-nodes to the Fluid template.

#. Third, we create the Fluid template. However, instead of outputting the contents directly using
   object accessors, we'll again use the `<ts:renderTypoScript>` ViewHelper to defer rendering to
   TypoScript again, and passing the needed TYPO3CR Node as context to TypoScript::

	{namespace neos=TYPO3\Neos\ViewHelpers}
	{namespace ts=TYPO3\TypoScript\ViewHelpers}
	<neos:contentElement node="{node}">
	  <ts:renderTypoScript path="videoRenderer" context="{node: video0}" />
	  <ts:renderTypoScript path="textRenderer" context="{node: text0}" />

	  <br />

	  <ts:renderTypoScript path="videoRenderer" context="{node: video1}" />
	  <ts:renderTypoScript path="videoRenderer" context="{node: text1}" />
	</neos:contentElement>

Instead of referencing specific content types directly as in the above example, it is often helpful
to reference a generic `Section` content element instead: This allows to insert *arbitrary content*
inside!

.. TODO: how can we add constraints on what types of contents are allowed inside sections?

.. TODO: shouldn't the "Image" TypoScript object have an additional property "maxWidth" and/or "maxHeight"
.. such that we can adjust the max width/height inside a given context directly?

Now, you might wonder about the benefits of the above rendering definition, as it might seem overly
complex for simple applications. The key benefit of the above architecture is its *composability*,
so one can re-use other TypoScript objects for rendering. Furthermore, the above architecture allows
to declaratively *adjust rendering* depending on constraints, which we will explain in the next section.


Processors
----------

TODO: PROCESSORS ERKLÄREN


Advanced Rendering Adjustments
------------------------------

Let's say we want to adjust our `YouTube` content element depending on the context: By default,
it renders in a standard YouTube video size; but when being used inside the sidebar of the page,
it should shrink to a width of 200 pixels. This is possible through *nested prototypes*::

	page.body.sections.sidebar.prototype(My.Package:YouTube) {
	  width = '200'
	  height = '150'
	}

Essentially the above code can be read as: "For all YouTube elements inside the sidebar of the page,
set width and height".

Let's say we also want to adjust the size of the YouTube video when being used in a `ThreeColumn`
element. This time, we cannot make any assumptions about a fixed TypoScript path being rendered,
because the `ThreeColumn` element can appear both in the main column, in the sidebar and nested
inside itself. However, we are able to *nest prototypes into each other*::

	prototype(ThreeColumn).prototype(My.Package:YouTube) {
	  width = '200'
	  height = '150'
	}

This essentially means: "For all YouTube elements which are inside ThreeColumn elements, set width
and height".

The two possibilities above can also be flexibly combined. Basically this composability allows to
adjust the rendering of websites and web applications very easily, without overriding templates completely.

After you have now had a head-first start into TypoScript based on practical examples, it is now
time to step back a bit, and explain the internals of TypoScript and why it has been built this way.


Inside TypoScript
=================

In this chapter, TypoScript will be explained in a step-by-step fashion, focussing on the different
internal parts, the syntax of these and the semantics.

TypoScript is fundamentally a *hierarchical, prototype based processing language*:

* It is *hierarchical* because the content it should render is also hierarchically structured.

* It is *prototype based* because it allows to define properties for *all instances* of a certain
  TypoScript object type. It is also possible to define properties not for all instances, but only
  for *instances inside a certain hierarchy*. Thus, the prototype definitions are hierarchically-scoped
  as well.

* It is a *processing language* because it processes the values in the *context* into a *single output
  value*.

In the first part of this chapter, we will explain the syntactic and semantic features of the TypoScript,
Eel and FlowQuery languages. Then, we will focus on the design decisions and goals of TypoScript, such that
the reader can get a better understanding of the main objectives we had in mind designing the language.

TypoScript Objects
------------------

TypoScript is a language to describe *TypoScript objects*. A TypoScript object has some *properties*
which are used to configure the object. Additionally, a TypoScript object has access to a *context*,
which is a list of variables. The goal of a TypoScript object is to take the variables from the
context, and transform them to the desired *output*, using its properties for configuration as needed.

Thus, TypoScript objects take some *input* which is given through the context and the properties, and
produce a single *output value*. Internally, they can modify the context, and trigger rendering of
nested TypoScript objects: This way, a big task (like rendering a whole web page) can be split into
many smaller tasks (render a single image, render a text, ...): The results of the small tasks are then
again put together, forming the final end result.

Because it is a fundamental principle that TypoScript objects call nested TypoScript objects, the rendering
process forms a *tree* of TypoScript objects, which can also be inspected using a TypoScript debugger.

TypoScript objects are implemented by a PHP class, which is instanciated at runtime. A single PHP class
is the basis for many TypoScript objects. We will highlight the exact connection between TypoScript
objects and their PHP implementations at a later chapter.

A TypoScript object can be instanciated by assigning it to a TypoScript path, such as::

	foo = Page
	# or:
	my.object = Text
	# or:
	my.image = TYPO3.Neos.ContentTypes:Image

You see that the name of the to-be-instanciated TypoScript prototype is listed without quotes.

By convention, TypoScript paths (such as `my.object`) are written in `lowerCamelCase`, while
TypoScript prototypes (such as `TYPO3.Neos.ContentTypes:Image`) are written in `UpperCamelCase`.

Now, we are able to set *properties* on the newly created TypoScript objects::

	foo.myProperty1 = 'Some Property which Page can access'
	my.object.myProperty1 = "Some other property"
	my.image.width = ${q(node).property('foo')}

You see that properties have to be quoted (with either single or double quotes), or can be an
*Eel expression* (which will be explained in a separate section lateron).

In order to reduce typing overhead, curly braces can be used to "abbreviate" long TypoScript paths,
as the following example demonstrates::

	my {
	  image = Image
	  image.width = 200

	  object {
	    myProperty1 = 'some property'
	  }
	}

Furthermore, you can also instanciate a TypoScript object and set properties on it in a single
pass, as shown in the third example below::

	# all three examples mean exactly the same.

	someImage = Image
	someImage.foo = 'bar'

	# Instanciate object, set property one after each other
	someImage = Image
	someImage {
	  foo = 'bar'
	}

	# Instanciate an object and setting properties directly
	someImage = Image {
	  foo = 'bar'
	}

In the next section, we will learn what is exactly done on object creation, i.e. when you type
`someImage = Image`.

.. admonition:: TypoScript Objects are Side-Effect Free

	When TypoScript objects are rendered, they are allowed to modify the TypoScript context
	(i.e. they can add, or override variables); and can invoke other TypoScript objects.
	After that, however, the parent TypoScript object must make sure to clean up the context,
	such that it contains exactly the state before its rendering.

	The API helps to enforce that, as the TypoScript context is a *stack*: The only thing the
	developer of a TypoScript object needs to make sure is that if he adds some variable to
	the stack, effectively creating a new stack frame, he needs to remove exactly this stack
	frame after rendering again.

	This means that a TypoScript object can only manipulate TypoScript objects *below it*,
	but not following or preceeding it.

	In order to enforce this, TypoScript objects are furthermore only allowed to communicate
	through the TypoScript Context; and they are never allowed to be invoked directly: Instead,
	all invocations need to be done through the *TypoScript Runtime*.

	All these constraints make sure that a TypoScript object is *side-effect free*, leading
	to an important benefit: If somebody knows the exact path towards a TypoScript object together
	with its context, it can be rendered in a stand-alone manner, exactly as if it was embedded
	in a bigger element. This enables f.e. to render parts of pages with different cache life-
	times, or the effective implementation of AJAX or ESI handlers reloading only parts of a
	website.


TypoScript Prototypes
---------------------

When a TypoScript object is instanciated, the *TypoScript Prototype* for this object is *copied*
and is taken as a basis. The prototype is defined using the following syntax::

	# we prefer this syntax:
	prototype(MyImage) {
		width = '500px'
		height = '600px'
	}

	# could also be written as:
	prototype(MyImage).width = '500px'
	prototype(MyImage).height = '500px'

Now, when the above prototype is instanciated, the instanciated object will have all the properties
of the prototype copied. This is illustrated through the following example::

	someImage = MyImage
	# now, someImage will have a width of 500px and a height of 600px

	someImage.width = '100px'
	# now, we have overridden the height of "someImage" to be 100px.

.. admonition:: Prototype- vs class-based languages

	There are generally two major "flavours" of object-oriented languages. Most languages
	(such as PHP, Ruby, Perl, Java, C++) are *class-based*, meaning that they explicitely
	distinguish between the place where behavior for a given object is defined (the "class")
	and the runtime representation which contains the data (the "instance").

	Other languages such as JavaScript are prototype-based, meaning that there is no distinction
	between classes and instances: At object creation time, all properties and methods of
	the object's *prototype* (which roughly corresponds to a "class") are copied (or otherwise
	referenced) to the *instance*.

	TypoScript is a *prototype-based language* because it *copies* the TypoScript Prototype
	to the instance when an object is evaluated.


Prototypes in TypoScript are *mutable*, which means that they can easily be modified::

	prototype(MyYouTube) {
		width = '100px'
		height = '500px'
	}

	# you can easily change the width/height, or define new properties:
	prototype(MyYouTube).width = '400px'
	prototype(MyYouTube).showFullScreen = ${true}

So far, we have seen how to define and instanciate prototypes from scratch. However, often
you will want to use an *existing TypoScript prototype* as basis for a new one. This can be
currently done by *subclassing* a TypoScript prototype using the `<` operator::

	prototype(MyImage) < prototype(Template)

	# now, the MyImage prototype contains all properties of the Template
	# prototype, and can be further customized.

We implement *prototype inheritance*, meaning that the "subclass" (`MyImage` in the example
above) and the "parent class (`Template`) are still attached to each other: If a property
is added to the parent class, this also applies to the subclass, as the following example
demonstrates::

	prototype(Template).fruit = 'apple'
	prototype(Template).meal = 'dinner'

	prototype(MyImage) < prototype(Template)
	# now, MyImage also has the properties "fruit = apple" and "meal = dinner"

	prototype(Template).fruit = 'Banana'
	# because MyImage *extends* Template, MyImage.fruit equals 'Banana' as well.

	prototype(MyImage).meal = 'breakfast'
	prototype(Template).meal = 'supper'
	# because MyImage now has an *overridden* property "meal", the change of
	# the parent class' property is not reflected in the MyImage class

	
.. admonition:: Prototype Inheritance is only allowed at top level

	Currently, prototype inerhitance can only be defined *globally*, i.e. with
	a statement of the following form::

		prototype(Foo) < prototype(Bar)

	It is not allowed to nest prototypes when defining prototype inheritance,
	so the following examples are **not valid TypoScript** and will result in
	an exception::

		prototype(Foo) < some.prototype(Bar)
		other.prototype(Foo) < prototype(Bar)
		prototype(Foo).prototype(Bar) < prototype(Baz)

	While it would be theoretically possible to support this, we have chosen
	not to do so in order to reduce complexity and to keep the rendering process
	more understandable. We have not yet seen a TypoScript example where a construct
	such as the above would be needed.

Namespaces of TypoScript objects
--------------------------------

.. TODO Robert: explain namespacing of TypoScript prototypes


Hierarchical TypoScript Prototypes
----------------------------------

One way to flexibly adjust the rendering of a TypoScript object is done through
modifying its *Prototype* in certain parts of the rendering tree. This is possible
because TypoScript prototypes are *hierarchical*, meaning that `prototype(...)`
can be part of any TypoScript path in an assignment; even multiple times::

	# the following are all valid TypoScript assignments, all with different
	# semantics
	prototype(Foo).bar = 'baz'
	prototype(Foo).some.thing = 'baz2'
	some.path.prototype(Foo).some = 'baz2'
	prototype(Foo).prototype(Bar).some = 'baz2'
	prototype(Foo).left.prototype(Bar).some = 'baz2'

Let's dissect these examples one by one:

* `prototype(Foo).bar` is a simple, top-level prototype property assignment. It means:
  *For all objects of type `Foo`, set property `bar`*. The second example is another variant
  of this pattern, just with more nesting levels inside the property assignment.

* `some.path.prototype(Foo).some` is a prototype property assignment *inside `some.path`*.
  It means: *For all objects of type `Foo` which occur inside the TypoScript path `some.path`,
  the property `some` is set.*

* `prototype(Foo).prototype(Bar).some` is a prototype property assignment *inside another
  prototype*. It means: *For all objects of type `Bar` which occur somewhere inside an
  object of type `Foo`, the property `some` is set.*

* This can both be combined, as in the last example inside `prottoype(Foo).left.prototype(Bar).some`.

.. admonition:: Internals of hierarchical prototypes

	We stated before that a TypoScript object is side-effect free, meaning that it can be
	rendered deterministically just knowing its *TypoScript path* and the *context*. In order
	to make this work with hierarchical prototypes, we need to encode the types of all TypoScript
	objects above the current one into the current path. This is done using angular brackets::

		a1/a2<Foo>/a3/a4<Bar>

	when this path is rendered, we know that at `a1/a2`, a TypoScript object of type `Foo` has
	been rendered -- which is needed to apply the prototype inheritance rules correctly.

Bottom line: You do not need to know exactly how the *TypoScript path* towards the currently
rendered TypoScript object is constructed, you just need to pass it on without modification
if you want to render a single element out-of-band. 

Setting Properties On a TypoScript Object
-----------------------------------------

Now, we have dissected the main building principles of TypoScript objects, and we're turning
towards smaller -- but nevertheless important -- building blocks inside TypoScript. We will now
focus on how exactly properties are set in a TypoScript object.

Besides simple assignments such as `myObject.foo = 'bar'` (which are a bit boring), one can write
*expressions* using the *Eel language* such as `myObject.foo = ${q(node).property('bar')}`.

Although the TypoScript object can read its context directly, it is a better practice to
instead use *properties* for configuration::

	# imagine that there is a property "foo=bar" inside the TypoScript context at this point
	myObject = MyObject

	# we explicitely take the "foo" variable's value from the context and pass it into the "foo"
	# property of myObject. This way, the flow of data is better visible.
	myObject.foo = ${foo}

While myObject could rely on the assumption that there is a "foo" variable inside the TypoScript
context, it has no way (besides written documentation) to communicate this to the outside world.

Thus, we encourage that a TypoScript object's implementation should *only use properties* of itself
to determine its output, and be independent of what is stored in the context.

However, in the prototype of this TypoScript object it is perfectly legal to store the mapping
between the context variables and TypoScript properties, such as in the following example::

	# this way, an explicit default mapping between a context variable and a property of the
	# TypoScript object is created.
	prototype(MyObject).foo = ${foo}


To sum it up: If you implement a TypoScript object, it should not access its context variables
directly, but instead use a property. In the TypoScript object's prototype, a default mapping
between a context variable and the prototype can be made.


Manipulating the TypoScript Context
-----------------------------------

Now that we have seen how the properties of a TypoScript object are evaluated, we're now turning
our focus to changing the TypoScript context.

This is possible through the use of the `@override` meta-property::

	myObject = MyObject
	myObject.@override.foo = ${bar * 2}

In the above example, there is now an additional context variable `foo` with twice the value
of `bar`.

This functionality is especially helpful if there are strong conventions regarding the TypoScript
context variables; which is often the case in standalone TypoScript applications.

For Neos, this functionality is hardly ever used.

.. TODO: is @override final in regard to the naming?

Processors
----------

.. TODO: Processors and eel should be able to work together
.. TODO: processor ordering should adhere to @override notation


Eel -- Embedded Expression Language
-----------------------------------

The Embedded Expression Language *Eel* is a building block for creating Domain Specific Languages.
It provides a rich *syntax* for arbitrary expressions, such that the author of the DSL can focus
on its Semantics.

In this section, we will focus on the use of Eel inside TypoScript.

Syntax
~~~~~~

Every Eel expression in TypoScript is surrounded by `${...}`, which is the delimiter for Eel
expressions. Basically, the Eel syntax and semantics is like a condensed version of JavaScript::

* Most things you can write as a single JavaScript expression (that is, without a `;`) can also
  be written as Eel expression.

* Eel does not throw an error if `null` values are dereferenced, i.e. inside `${foo.bar}`
  with `foo` being `null`. Instead, `null` is returned. This also works for calling undefined
  functions.

* We do not support control structures or variable declarations.

* We support the common JavaScript arithmetic and comparison operators, such as `+-*/%` for
  arithmetic and `== != > >= < <=` for comparison operators. Operator precedence is as expected,
  with multiplication binding higher than addition. This can be adjusted by using brackets. Boolean
  operators `&&` and `||` are supported.

* We support the ternary operator to allow for conditions `<condition> ? <ifTrue> : <ifFalse>`.

* When object access is done (such as `foo.bar.baz`) on PHP objects, getters are called automatically.

* Object access with the offset notation is supported: `foo['bar']`

This means the following expressions are all valid Eel expressions::

	${foo}
	${foo.bar}
	${f()}
	${f().g()}
	${f() ? g : h + i * 5}


Semantics inside TypoScript
~~~~~~~~~~~~~~~~~~~~~~~~~~~

Eel does not define any functions or variables by itself. Instead, it exposes the *Eel context
array*, such that functions and objects which should be accessible can be defined there.

Because of that, Eel is perfectly usable as a "domain-specific language construction kit", which
provides the syntax, but not the semantics of a given language.

*For Eel inside TypoScript, we have defined a semantics which is outlined below:*

* All variables of the TypoScript context are made available inside the Eel context.

* Additionally, the function `q()` is available, which wraps its argument into a FlowQuery
  object. FlowQuery is explained below.

* Last, the special variable `this` always points to the current TypoScript object implementation.

Here follows an example usage in the context of TypoScript::

	${node}
	${myContextVariable}
	${node.getProperty('foo')} # discouraged. You should use FlowQuery instead.
	${q(node).property('foo')}

.. TODO: Eel Standard Library

FlowQuery and Fizzle
--------------------
- flowquery (syntax, examples on nodes)
- fizzle (TODO: check if syntax is final)



Planned Extension Points using Case and Collection
--------------------------------------------------




Goals of TypoScript
-------------------

- both for planned and unplanned extensibility
- also used for standalone, extensible applications (though that is not relevant
  in this guide)
- out-of-band rendering easily possible
- multiple renderings of the same content
-
- …
- inspiration sources (see issue) http://forge.typo3.org/issues/31638
-- css, jQuery (flowQuery, eel, ...), xpath, JS


important TypoScript objects and patterns
=========================================

- page, template, section, menu, value (TODO ChristianM)


TypoScript internals
====================

- @class, backed by PHP class
- DOs and DONT's when implementing custom TypoScript objects
- implementing custom FlowQuery operations

Standalone Usage of TypoScript
-> eigene Dokumentation
Standalone Usage of Eel & FlowQuery
-> eigene Dokumentation