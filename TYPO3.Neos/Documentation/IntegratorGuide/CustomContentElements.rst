.. _custom-content-elements:

================================
Creating Custom Content Elements
================================

In TYPO3 Neos, it is very easy to create custom content elements. Neos ships
with commonly used, predefined content elements, but it is easily possible to
amend and even completely replace them.

Defining new content elements is usually a three-step process:

#. Define the *TYPO3CR Node Type*, listing the properties and types of the node.

#. Define a *TypoScript object* which is responsible for rendering this content type.
   Usually, this is a wrapper for a Fluid Template which then defines the rendered
   markup.

#. Add a *Fluid Template* which contains the markup being rendered

The following example creates a new content element `My.Package:YouTube` which needs
the YouTube URL and then renders the video player.

First, the *TYPO3CR Node Type* needs to be defined in `NodeTypes.yaml`::

	'My.Package:YouTube':
	  superTypes: ['TYPO3.Neos.ContentTypes:ContentObject']
	  ui:
	    group: 'General'
	    label: 'YouTube Video'
	  properties:
	    videoUrl:
	      type: string
	      ui:
	        label: 'Video URL'

Then, we have to define the TypoScript rendering for this content element. By convention,
a TypoScript object with the same name as the content element is used for rendering; thus
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

	* Because the rendering is decoupled from the data storage this way, we can easily instantiate the
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
=============================

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
==========

TODO: PROCESSORS ERKLÄREN


Advanced Rendering Adjustments
==============================

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
