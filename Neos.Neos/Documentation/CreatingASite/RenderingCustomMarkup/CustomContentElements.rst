.. _custom-content-elements:

================================
Creating Custom Content Elements
================================

Neos ships with commonly used, predefined content elements, but it is easily possible
to amend and even completely replace them.

Defining new content elements is usually a three-step process:

#. Defining a *Neos ContentRepository Node Type*, listing the properties and types of the node.

#. Defining a *Fusion object* which is responsible for rendering this content type.
   Usually, this is a wrapper for a Fluid Template which then defines the rendered
   markup.

#. Add a *Fluid Template* which contains the markup being rendered

Creating a Simple Content Element
=================================

The following example creates a new content element `Acme.Demo:YouTube` which needs
the YouTube URL and then renders the video player.

First, the *Neos ContentRepository Node Type* needs to be defined in `NodeTypes.yaml`. This can be done
in your site package or in a package dedicated to content elements, if reuse is foreseeable.

::

	   'Acme.Demo:YouTube':
	     superTypes:
	      'Neos.Neos:Content': TRUE
	     ui:
	       group: 'general'
	       label: 'YouTube Video'
	       inspector:
	         groups:
	           video:
	             label: 'Video'
	             icon: 'icon-film'
	     properties:
	       videoUrl:
	         type: string
	         ui:
	           label: 'Video URL'
	           reloadIfChanged: TRUE
	           inspector:
	             group: 'video'

The declaration of node types with all required and optional properties is documented in
:ref:`node-type-definition`.

Next the Fusion rendering for the content element has to be defined. By convention,
a Fusion object with the same name as the content element is used for rendering; thus
in this case a Fusion object `My.Package:YouTube`::

	prototype(Acme.Demo:YouTube) < prototype(Neos.Neos:Content) {
		templatePath = 'resource://Acme.Demo/Private/Templates/FusionObjects/YouTube.html'
		videoUrl = ${q(node).property('videoUrl')}
		width = '640'
		height = '360'
	}

A new Fusion object prototype with the name `My.Package:YouTube` is declared, inheriting
from the pre-defined `Template` Fusion object which provides rendering through Fluid.

The `templatePath` property of the `YouTube` Fusion object is set to point to the
Fluid template to use for rendering. All (other) properties which are set on the `Template`
Fusion object are directly made available inside Fluid as variables -- and
because the `YouTube` Fusion object extends the `Template` Fusion object, this
rule also applies there.

Thus, the last line defines a `videoUrl` variable to be available inside Fluid, which is
set to the result of the Eel expression `${q(node).property('videoUrl')}`. Eel is explained
in depth in :ref:`eel-flowquery`, but this is a close look at the used expression
`q(node).property('videoUrl')`:

* The q() function wraps its argument, in this case the Neos ContentRepository Node which is currently rendered,
  into *FlowQuery*.

* FlowQuery defines the `property(...)` operation used to access the property of a node.

To sum it up: The expression `${q(node).property('videoUrl')}` is an Eel expression, in which
FlowQuery is called to return the property `videoUrl` of the current node.

The final step in creating the YouTube content element is defining the `YouTube.html` Fluid
template, f.e. with the following content::

	<iframe width="{width}" height="{height}" src="{videoUrl}" frameborder="0" allowfullscreen></iframe>

In the template the `{videoUrl}` variable which has been defined in Fusion is used as we need it.

What are the benefits of indirection through Fusion?
--------------------------------------------------------

In the above example the `videoUrl` property of the *Node* is not directly rendered inside the
Fluid template. Instead *Fusion* is used to pass the `videoUrl` from the *Node* into the Fluid
template.

While this indirection might look superfluous at first sight, it has important benefits:

* The Fluid Template does not need to know anything about *Nodes*. It just needs to know
  that it outputs a certain property, but not where it came from.

* Because the rendering is decoupled from the data storage this way, the Fusion object can be
  instantiated directly, manually setting a `videoUrl`::

    page.body.parts.teaserVideo = My.Package:YouTube {
      videoUrl = 'http://youtube.com/.....'
    }

* If a property needs to be modified *just slightly*, a *processor* can be used for declarative
  modification of this property in Fusion; not even touching the Fluid template. This is helpful
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
	  superTypes:
	    'Neos.Neos:Content': TRUE
	  ui:
	    group: 'general'
	    label: 'Quote'
	  properties:
	    quote:
	      type: string
	      defaultValue: 'Use the force, Luke!'
	      ui:
	        label: 'Quote'
	        inlineEditable: TRUE

The Fusion for the content element is the same as for a non-inline-editable content
element::

	prototype(Acme.Demo:Quote) < prototype(Neos.Neos:Content) {
		templatePath = 'resource://Acme.Demo/Private/Templates/FusionObjects/Quote.html'
		quote = ${q(node).property('quote')}
	}

The Fluid template again needs some small adjustment in form of the `contentElement.editable`
ViewHelper to declare the property that is editable. This may seem like duplication, since the
node type already declares the editable properties. But since in a template multiple editable
properties might be used, this still is needed.

::

	{namespace neos=Neos\Neos\ViewHelpers}
	<blockquote>
		{neos:contentElement.editable(property: 'quote')}
	</blockquote>

The ``blockquote`` is wrapped around the `contentElement.editable` and not the other way because that would
mean the blockquote becomes a part of the editable content, which is not desired in this case.

Using the `tag` attribute to make the ViewHelper use the ``blockquote`` tag needed for the element
avoids the nesting in an additional container `div` and thus cleans up the generated markup::

	{namespace neos=Neos\Neos\ViewHelpers}
	{neos:contentElement.editable(property: 'quote', tag: 'blockquote')}

A property can be inline editable *and* appear in the property inspector if configured accordingly. In
such a case `reloadIfChanged` should be enabled to make changes in the property editor visible in the
content area.

Creating Nested Content Elements
================================

In case content elements do not only contain simple properties, but arbitrary sub-elements, the process
again is roughly the same. To demonstrate this, a `Video Grid` content element will be created, which
can contain two texts and two videos.

#. A Neos ContentRepository Node Type definition is created. It makes use of the `childNodes` property to define
   (and automatically create) sub-nodes when a node of this type is created. In the example the two
   video and text elements will be created directly upon element creation::

	'Acme.Demo:VideoGrid':
	  superTypes:
	    'Neos.Neos:Content': TRUE
	  ui:
	    group: 'structure'
	    label: 'Video Grid'
	  childNodes:
	    video0:
	      type: 'Acme.Demo:YouTube'
	    video1:
	      type: 'Acme.Demo:YouTube'
	    text0:
	      type: 'Neos.NodeTypes:Text'
	    text1:
	      type: 'Neos.NodeTypes:Text'

#. The needed Fusion is created::

	prototype(Acme.Demo:VideoGrid) {
		videoRenderer = Acme.Demo:YouTube
		textRenderer = Neos.NodeTypes:Text

		video0 = ${q(node).children('video0').get(0)}
		video1 = ${q(node).children('video1').get(0)}

		text0 = ${q(node).children('text0').get(0)}
		text1 = ${q(node).children('text1').get(0)}
	}

   Instead of assigning variables to the Fluid template, *additional Fusion objects* responsible
   for the video and the text rendering are instantiated. Furthermore, the video and text nodes
   are fetched using Eel and then passed to the Fluid template.

#. The Fluid template is created. Instead of outputting the content directly using object access
   on the passed nodes, the `<ts:render>` ViewHelper is used to defer rendering to
   Fusion again. The needed Neos ContentRepository Node is passed as context to Fusion::

	{namespace fusion=Neos\Fusion\ViewHelpers}
	<fusion:render path="videoRenderer" context="{node: video0}" />
	<fusion:render path="textRenderer" context="{node: text0}" />
	<br />
	<fusion:render path="videoRenderer" context="{node: video1}" />
	<fusion:render path="textRenderer" context="{node: text1}" />

Instead of referencing specific content types directly the use of the generic `ContentCollection` content
element allows to insert *arbitrary content* inside other elements. An example can be found in the
`Neos.NodeTypes:MultiColumn` and `Neos.NodeTypes:MultiColumnItem` content elements.

As explained earlier (in `What are the benefits of indirection through Fusion?`_) the major benefit
if using Fusion to decouple the rendering of items this way is flexibility. In the video grid
it shows how this enables *composability*, other Fusion objects can be re-used for rendering
smaller parts of the element.

Content Element Group
=====================

In Neos content elements are grouped by type. By default the following groups are available:

`general`
	Basic content elements, like `text` and `image`.

`structure`
	Elements defining a structure. This group contains for example the 2 column element.

`plugins`
	Available plugins in the site installation.

It is possible to create new groups by using the `Neos.Neos.nodeTypes.groups` settings.
Registering 2 new groups could look like::

	TYPO3:
	  Neos:
	    nodeTypes:
	      groups:
	        form:
	          label: 'Form elements'
	        special:
	          position: 50
	          label: 'Special elements'
	          collapsed: true
	          icon: 'icon-fort-awesome'

The groups are ordered by the position argument.

Extending The Inspector
=======================

.. warning:: Adding editors and validators is no fixed API yet, keep an eye on the changelogs if you use this.

It is possible to extend the inspector for adding new editors and validators to edit the properties
of your nodetypes.

Editors
-------

By default the following list of editors is available in Neos:

* `Neos.Neos/Inspector/Editors/BooleanEditor`

  A checkbox, by default configured for properties of type `boolean`.

* `Neos.Neos/Inspector/Editors/DateTimeEditor`

  A datepicker with support for time selection too. By default configured for properties
  of type `date`.

* `Neos.Neos/Inspector/Editors/CodeEditor`

  An code editor with syntax highlighting. You can use this editor for editing
  other types of *textual* content, by configuring a different `highlightingMode` and
  `buttonLabel` to change usage for this editor::

    style:
      type: string
      ui:
        label: 'CSS'
        reloadIfChanged: TRUE
        inspector:
          group: 'code'
          editor: 'Neos.Neos/Inspector/Editors/CodeEditor'
          editorOptions:
            buttonLabel: 'Edit CSS source'
            highlightingMode: 'text/css'

* `Neos.Neos/Inspector/Editors/ImageEditor`

  An image editor with cropping and size support. By default configured for properties
  of type `Neos\Media\Domain\Model\ImageInterface`.

* `Neos.Neos/Inspector/Editors/ReferenceEditor`

  A selector with autocomplete to reference to another node. By default configured for
  properties of type `reference`.

* `Neos.Neos/Inspector/Editors/ReferencesEditor`

  A selector with autocomplete to reference to multiple nodes. By default configured for
  properties of type `references`.

* `Neos.Neos/Inspector/Editors/SelectBoxEditor`

  A selectbox.

* `Neos.Neos/Inspector/Editors/TextFieldEditor`

  A simple textfield. By default configured for properties of type `string` and `integer`

The following editors are also available, but will most likely only be used internally in Neos:

* `Neos.Neos/Inspector/Editors/MasterPluginEditor`
* `Neos.Neos/Inspector/Editors/PluginViewEditor`
* `Neos.Neos/Inspector/Editors/PluginViewsEditor`

Register Custom Editors
~~~~~~~~~~~~~~~~~~~~~~~

There are 2 ways to register custom editors. Either by registering a namespace for a group
of editors, or by selecting the direct path to an editor specifically.

Registering a namespace pointing to a folder containing editors works as follows:

* Create a folder containing the JavaScript sources for the editors
* Name your files `PropertyTypeEditor`
* Configure the path as a requirejs path mapping using the following Settings.yaml

  ::

    Neos:
      Neos:
        userInterface:
          requireJsPathMapping:
            'My.Package/Inspector/Editors': 'resource://My.Package/Public/Scripts/Path/To/Folder'

* Now configure the editor for your property in the NodeTypes.yaml:

  ::

    'My.Package:NodeType':
      properties:
        myProperty:
          type: 'string'
          ui:
            inspector:
              editor: 'My.Package/Inspector/Editors/PropertyTypeEditor'
              editorOptions:
                optionName: 'optionValue'

To set global options for your editor you can set a set of defaults in Settings.yaml:

::

    Neos:
      Neos:
        userInterface:
          inspector:
            editors:
              'My.Package/Inspector/Editors/PropertyTypeEditor':
                editorOptions:
                  optionName: 'optionValue'

The editor options set on a property level will override the global editor options.

To register just one specific path as an editor use the following code:

::

  Neos:
    Neos:
      userInterface:
        inspector:
          editors:
            'My.Package/Inspector/Editors/CustomEditor':
              path: 'resource://My.Package/Public/Scripts/Path/To/File/Without/Js/Extension'


Validators
----------

By default the following validators are available in Neos:

* `Neos.Neos/Validation/AbstractValidator`

  This *abstract* validator should be used to base custom validators on.

* `Neos.Neos/Validation/AlphanumericValidator`

  Supported options:

  * regularExpression

* `Neos.Neos/Validation/CountValidator`

  Supported options:

  * minimum
  * maximum

* `Neos.Neos/Validation/DateTimeRangeValidator`

  Supported options:

  * latestDate
  * earliestDate

* `Neos.Neos/Validation/DateTimeValidator`
* `Neos.Neos/Validation/EmailAddressValidator`

  Supported options:

  * regularExpression

* `Neos.Neos/Validation/FloatValidator`
* `Neos.Neos/Validation/IntegerValidator`
* `Neos.Neos/Validation/LabelValidator`

  Supported options:

  * regularExpression

* `Neos.Neos/Validation/NumberRangeValidator`

  Supported options:

  * minimum
  * maximum

* `Neos.Neos/Validation/RegularExpressionValidator`

  Supported options:

  * regularExpression

* `Neos.Neos/Validation/StringLengthValidator`

  Supported options:

  * minimum
  * maximum

* `Neos.Neos/Validation/StringValidator`
* `Neos.Neos/Validation/TextValidator`
* `Neos.Neos/Validation/UuidValidator`

  Supported options:

  * regularExpression

Register Custom Validators
~~~~~~~~~~~~~~~~~~~~~~~~~~

There are 2 ways to register custom validators. Either by registering a namespace for a group
of validators, or by selecting the direct path to an validator specifically.

Registering a namespace pointing to a folder containing validators works as follows:

* Create a folder containing the JavaScript sources for the validators
* Name your files `DataTypeValidator`
* Configure the path as a requirejs path mapping using the following Settings.yaml

  ::

    Neos:
      Neos:
        userInterface:
          requireJsPathMapping:
            'My.Package/Validation': 'resource://My.Package/Public/Scripts/Path/To/Folder'

* Now configure the validator for your property in the NodeTypes.yaml:

  ::

    'My.Package:NodeType':
      properties:
        myProperty:
          type: 'string'
          validation:
            'My.Package/Validation/DataTypeValidator': []

To register just one specific path as a validator use the following code:

::

  Neos:
    Neos:
      userInterface:
        validators:
          'My.Package/Validation/CustomValidator':
            path: 'resource://My.Package/Public/Scripts/Path/To/File/Without/Js/Extension'
