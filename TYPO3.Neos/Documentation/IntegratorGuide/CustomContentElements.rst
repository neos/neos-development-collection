.. _custom-content-elements:

================================
Creating Custom Content Elements
================================

Neos ships with commonly used, predefined content elements, but it is easily possible
to amend and even completely replace them.

Defining new content elements is usually a three-step process:

#. Defining a *TYPO3CR Node Type*, listing the properties and types of the node.

#. Defining a *TypoScript object* which is responsible for rendering this content type.
   Usually, this is a wrapper for a Fluid Template which then defines the rendered
   markup.

#. Add a *Fluid Template* which contains the markup being rendered

Creating a Simple Content Element
=================================

The following example creates a new content element `Acme.Demo:YouTube` which needs
the YouTube URL and then renders the video player.

First, the *TYPO3CR Node Type* needs to be defined in `NodeTypes.yaml`. This can be done
in your site package or in a package dedicated to content elements, if reuse is foreseeable.

::

	'Acme.Demo:YouTube':
	  superTypes: ['TYPO3.Neos.NodeTypes:ContentObject']
	  ui:
	    group: 'General'
	    label: 'YouTube Video'
		inspector:
		  groups:
			video:
			  label: 'Video'
	  properties:
	    videoUrl:
	      type: string
	      ui:
	        label: 'Video URL'
			inspector:
			  group: 'video'
			reloadIfChanged: TRUE

The declaration of node types with all required and optional properties is documented in
:ref:`node-type-definition`.

Next the TypoScript rendering for the content element has to be defined. By convention,
a TypoScript object with the same name as the content element is used for rendering; thus
in this case a TypoScript object `My.Package:YouTube`::

	prototype(Acme.Demo:YouTube) < prototype(TYPO3.TypoScript:Template) {
		templatePath = 'resource://Acme.Demo/Private/Templates/TypoScriptObjects/YouTube.html'
		videoUrl = ${q(node).property('videoUrl')}
		width = '640'
		height = '360'
	}

A new TypoScript object prototype with the name `My.Package:YouTube` is declared, inheriting
from the pre-defined `Template` TypoScript object which provides rendering through Fluid.

The `templatePath` property of the `YouTube` TypoScript object is set to point to the
Fluid template to use for rendering. All (other) properties which are set on the `Template`
TypoScript object are directly made available inside Fluid as variables -- and
because the `YouTube` TypoScript object extends the `Template` TypoScript object, this
rule also applies there.

Thus, the last line defines a `videoUrl` variable to be available inside Fluid, which is
set to the result of the Eel expression `${q(node).property('videoUrl')}`. Eel is explained
in depth in :ref:`eel-flowquery`, but this is a close look at the used expression
`q(node).property('videoUrl')`:

* The q() function wraps its argument, in this case the TYPO3CR Node which is currently rendered,
  into *FlowQuery*.

* FlowQuery defines the `property(...)` operation used to access the property of a node.

To sum it up: The expression `${q(node).property('videoUrl')}` is an Eel expression, in which
FlowQuery is called to return the property `videoUrl` of the current node.

The final step in creating the YouTube content element is defining the `YouTube.html` Fluid
template, f.e. with the following content::

	{namespace n=TYPO3\Neos\ViewHelpers}
	<n:contentElement node="{node}">
		<iframe width="{width}" height="{height}" src="{videoUrl}" frameborder="0" allowfullscreen></iframe>
	</n:contentElement>

In the template the `{videoUrl}` variable which has been defined in TypoScript is used as we need it.

The only required Neos specific markup in the template is the wrapping of the whole content element
with the `<n:contentElement>` ViewHelper, which is needed to make the content element selectable
inside the Neos backend.

What are the benefits of indirection through TypoScript?
--------------------------------------------------------

	In the above example the `videoUrl` property of the *Node* is not directly rendered inside the
	Fluid template. Instead *TypoScript* is used to pass the `videoUrl` from the *Node* into the Fluid
	template.

	While this indirection might look superfluous at first sight, it has important benefits:

	* The Fluid Template does not need to know anything about *Nodes*. It just needs to know
	  that it outputs a certain property, but not where it came from.

	* Because the rendering is decoupled from the data storage this way, the TypoScript object can be
	  instantiated directly, manually setting a `videoUrl`::

		page.body.parts.teaserVideo = My.Package:YouTube {
		  videoUrl = 'http://youtube.com/.....'
		}

	* If a property needs to be modified *just slightly*, a *processor* can be used for declarative
	  modification of this property in TypoScript; not even touching the Fluid template. This is helpful
	  for smaller adjustments to foreign packages.

Creating Editable Content Elements
==================================

The simple content element created in `Creating a Simple Content Element`_ exposes the video URL
only through the property inspector in the editing interface. Since the URL is not directly visible
this is the only viable way.

In case of content that is directly visible in the output, inline editing can be enabled by slight
adjustments to the process already explained.

The node type definition must define which properties are inline editable through setting the
`inlineEditable` property::

	'Acme.Demo:Quote':
	  superTypes: ['TYPO3.Neos.NodeTypes:ContentObject']
	  ui:
	    group: 'General'
	    label: 'Quote'
	  properties:
	    quote:
	      type: string
	      defaultValue: 'Use the force, Luke!'
	      ui:
	        label: 'Quote'
	        inlineEditable: TRUE

The TypoScript for the content element is the same as for a non-inline-editable content
element::

	prototype(Acme.Demo:Quote) < prototype(TYPO3.TypoScript:Template) {
		templatePath = 'resource://Acme.Demo/Private/Templates/TypoScriptObjects/Quote.html'
		quote = ${q(node).property('quote')}
	}

The Fluid template again needs some small adjustment in form of the `contentElement.editable`
ViewHelper to declare the property that is editable. This may seem like duplication, since the
node type already declares the editable properties. But since in a template multiple editable
properties might be used, this still is needed.

::

	{namespace n=TYPO3\Neos\ViewHelpers}
	<n:contentElement node="{node}">
		<blockquote>
			<n:contentElement.editable property="quote">{quote -> f:format.raw()}</n:contentElement.editable>
		</blockquote>
	</n:contentElement>

The ``blockquote`` is wrapped around the `contentElement.editable` and not the other way because that would
mean the blockquote becomes a part of the editable content, which is not desired in this case.

Using the `tag` attribute to make the ViewHelper use the ``blockquote`` tag needed for the element
avoids the nesting in an additional container `div` and thus cleans up the generated markup::

	{namespace n=TYPO3\Neos\ViewHelpers}
	<n:contentElement node="{node}">
		<n:contentElement.editable property="quote" tag="blockquote">{quote -> f:format.raw()}</n:contentElement.editable>
	</n:contentElement>

A property can be inline editable *and* appear in the property inspector if configured accordingly. In
such a case `reloadIfChanged` should be enabled to make changes in the property editor visible in the
content area.

Creating Nested Content Elements
================================

In case content elements do not only contain simple properties, but arbitrary sub-elements, the process
again is roughly the same. To demonstrate this, a `Video Grid` content element will be created, which
can contain two texts and two videos.

#. A TYPO3CR Node Type definition is created. It makes use of the `childNodes` property to define
   (and automatically create) sub-nodes when a node of this type is created. In the example the two
   video and text elements will be created directly upon element creation::

	'Acme.Demo:VideoGrid':
	  superTypes: ['TYPO3.Neos.NodeTypes:AbstractNode']
	  ui:
	    group: 'Structure'
	    label: 'Video Grid'
	  childNodes:
	    video0:
	      type: 'Acme.Demo:YouTube'
	    video1:
	      type: 'Acme.Demo:YouTube'
	    text0:
	      type: 'TYPO3.Neos.NodeTypes:Text'
	    text1:
	      type: 'TYPO3.Neos.NodeTypes:Text'

#. The needed TypoScript is created::

	prototype(Acme.Demo:VideoGrid) < prototype(TYPO3.TypoScript:Template) {
		templatePath = 'resource://Acme.Demo/Private/Templates/TypoScriptObjects/VideoGrid.html'

		videoRenderer = Acme.Demo:YouTube
		textRenderer = TYPO3.Neos.NodeTypes:Text

		video0 = ${q(node).children('video0').get(0)}
		video1 = ${q(node).children('video1').get(0)}

		text0 = ${q(node).children('text0').get(0)}
		text1 = ${q(node).children('text1').get(0)}
	}

   Instead of assigning variables to the Fluid template, *additional TypoScript objects* responsible
   for the video and the text rendering are instantiated. Furthermore, the video and text nodes
   are fetched using Eel and then passed to the Fluid template.

#. The Fluid template is created. Instead of outputting the content directly using object access
   on the passed nodes, the `<ts:renderTypoScript>` ViewHelper is used to defer rendering to
   TypoScript again. The needed TYPO3CR Node is passed as context to TypoScript::

	{namespace n=TYPO3\Neos\ViewHelpers}
	{namespace ts=TYPO3\TypoScript\ViewHelpers}
	<n:contentElement node="{node}">
		<ts:renderTypoScript path="videoRenderer" context="{node: video0}" />
		<ts:renderTypoScript path="textRenderer" context="{node: text0}" />

		<br />

		<ts:renderTypoScript path="videoRenderer" context="{node: video1}" />
		<ts:renderTypoScript path="textRenderer" context="{node: text1}" />
	</n:contentElement>

Instead of referencing specific content types directly the use of the generic `Section` content
element allows to insert *arbitrary content* inside other elements. An exmaple can be found in the
`TYPO3.Neos.NodeTypes:MultiColumn` and `TYPO3.Neos.NodeTypes:MultiColumnItem` content elements.

As explained earlier (in `What are the benefits of indirection through TypoScript?`_) the major benefit
if using TypoScript to decouple the rendering of items this way is flexibility. In the video grid
it shows how this enables *composability*, other TypoScript objects can be re-used for rendering
smaller parts of the element.
