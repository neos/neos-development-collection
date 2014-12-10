.. _content-structure:

===========================
The TYPO3 Content Structure
===========================

Before we can understand how content is rendered, we have to see how it is structured
and organized. These basics are explained in this section.

Nodes inside the TYPO3 Content Repository
=========================================

The content in Neos is stored not inside tables of a relational database, but
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
  a ``Text`` node might have a ``headline`` and a ``text`` property.
* Of course, nodes may have *sub nodes* underneath them.

If we imagine a classical website with a hierarchical menu structure, then each
of the pages is represented by a TYPO3CR Node of type ``Document``. However, not only
the pages themselves are represented as tree: Imagine a page has two columns,
with different content elements inside each of them. The columns are stored as
Nodes of type ``ContentCollection``, and they contain nodes of type ``Text``, ``Image``, or
whatever structure is needed. This nesting can be done indefinitely: Inside
a ``ContentCollection``, there could be another three-column element which again contains
``ContentCollection`` elements with arbitrary content inside.

.. admonition:: Comparison to TYPO3 CMS

	In TYPO3 CMS, the *page tree* is the central data structure, and the content
	of a page is stored in a more-or-less flat manner in a separate database table.

	Because this was too limited for complex content, TemplaVoila was invented.
	It allows to create an arbitrary nesting of content elements, but is still
	plugged into the classical table-based architecture.

	Basically, TYPO3 Neos generalizes the tree-based concept found in TYPO3 CMS
	and TemplaVoila and implements it in a consistent manner, where we do not
	have to distinguish between pages and other content.

.. _node-type-definition:

Node Type Definition
====================

Each TYPO3CR Node (we'll just call it Node in the remaining text) has a specific
*node type*. Node Types can be defined in any package by declaring them in
``Configuration/NodeTypes.yaml``.

Each node type can have *one or multiple parent types*. If these are specified,
all properties and settings of the parent types are inherited.

A node type definition can look as follows::

	'My.Package:SpecialHeadline':
	  superTypes: ['TYPO3.Neos:Content']
	  ui:
	    label: 'Special Headline'
	    group: 'general'
	  properties:
	    headline:
	      type: 'string'
	      defaultValue: 'My Headline Default'
	      ui:
	        inlineEditable: TRUE
	      validation:
	        'TYPO3.Neos/Validation/StringLengthValidator':
	          minimum: 1
	          maximum: 255

The following options are allowed:

``superTypes``
  An array of parent node types inherited from. If named keys are used, it is possible to remove a defined super type
  again for a specific nodetype, by setting the value for that key to ``~`` (NULL)::

    'TYPO3.Neos:Document':
      superTypes:
        'Acme.Demo.ExtraMixin': 'Acme.Demo:ExtraMixin'

    'TYPO3.Neos:Shortcut':
      superTypes:
        'Acme.Demo.ExtraMixin': ~


``constraints``
  Constraint definitions stating which nested child node types are allowed. Also see the dedicated chapter
  :ref:`node-constraints` for detailed explanation::

    constraints:
      nodeTypes:
        # ALLOW text, DISALLOW Image
        'TYPO3.Neos.NodeTypes:Text': TRUE
        'TYPO3.Neos.NodeTypes:Image': FALSE
        # DISALLOW as Fallback (for not-explicitely-listed node types)
        '*': FALSE

``childNodes``
  A list of child nodes that are automatically created if a node of this type is created.
  For each child the ``type`` has to be given. Additionally, for each of these child nodes,
  the ``constraints`` can be specified to override the "global" constraints per type.
  Here is an example::

    childNodes:
      someChild:
        type: 'TYPO3.Neos:ContentCollection'
        constraints:
          nodeTypes:
            # only allow images in this ContentCollection
            'TYPO3.Neos.NodeTypes:Image': TRUE
            '*': FALSE

``ui``
  Configuration options related to the user interface representation of the node type

  ``label``
    The human-readable label of the node type

  ``group``
    Name of the group this content element is grouped into for the 'New Content Element' dialog.
    It can only be created through the user interface if ``group`` is defined and it is valid.

    All valid groups are given in the ``TYPO3.Neos.nodeTypes.groups`` setting

  ``icon``
    This setting define the icon to use in the Neos UI for the node type

    Currently it's only possible to use a predefined selection of icons, which
    are available in Font Awesome http://fortawesome.github.io/Font-Awesome/3.2.1/icons/.

  ``inlineEditable``
    If TRUE, it is possible to interact with this Node directly in the content view.
    If FALSE, an overlay is shown preventing any interaction with the node.
    If not given, checks if any property is marked as ``ui.inlineEditable``.

  ``inspector``
    These settings configure the inspector in the Neos UI for the node type

    ``tabs``
      Defines an inspector tab that can be used to group property groups of the node type

      ``label``
        The human-readable label for this inspector tab

      ``position``
        Position of the inspector tab, small numbers are sorted on top

      ``icon``
        This setting define the icon to use in the Neos UI for the tab

        Currently it's only possible to use a predefined selection of icons, which
        are available in Font Awesome http://fortawesome.github.io/Font-Awesome/3.2.1/icons/.

    ``groups``
      Defines an inspector group that can be used to group properties of the node type

      ``label``
        The human-readable label for this inspector group

      ``position``
        Position of the inspector group, small numbers are sorted on top

      ``tab``
        The tab the group belongs to. If left empty the group is added to the ``default`` tab.

``properties``
  A list of named properties for this node type. For each property the following settings are available.

  ``type``
    Data type of this property. This may be a simple type (like in PHP), a fully qualified PHP class name, or one of
    these three special types: ``date``, ``references``, or ``reference``. Use ``date`` to store dates / time as a
    DateTime object. Use ``reference`` and ``references`` to store references that point to other nodes. ``reference``
    only accepts a single node or node identifier, while ``references`` accepts an array of nodes or node identifiers.

  ``defaultValue``
    Default value of this property. Used at node creation time. Type must match specified 'type'.

  ``ui``
    Configuration options related to the user interface representation of the property

    ``label``
      The human-readable label of the property

    ``reloadIfChanged``
      If TRUE, the whole content element needs to be re-rendered on the server side if the value
      changes. This only works for properties which are displayed inside the property inspector,
      i.e. for properties which have a ``group`` set.

    ``inlineEditable``
      If TRUE, this property is inline editable, i.e. edited directly on the page through Aloha.

    ``aloha``
      This section controls the text formatting options the user has available for this property.
      Example::

        aloha:
          'format': # Enable specific formatting options.
            'strong': TRUE
            'b': FALSE
            'em': TRUE
            'i': FALSE
            'u': TRUE
            'sub': TRUE
            'sup': TRUE
            'p': TRUE
            'h1': TRUE
            'h2': TRUE
            'h3': TRUE
            'h4': FALSE
            'h5': FALSE
            'h6': FALSE
            'code': FALSE
            'removeFormat': TRUE
          'table':
            'table': TRUE
          'link':
            'a': TRUE
          'list':
            'ul': TRUE
            'ol': TRUE
          'alignment':
            'left': TRUE
            'center': TRUE
            'right': TRUE
            'justify': TRUE
          'formatlesspaste':
            'button': TRUE # Show toggle button for formatless pasting.
            'formatlessPasteOption': FALSE # Whether the format less pasting should be enable by default.
            'strippedElements': ['a'] # If not set the default setting is used.
            'autoparagraph': TRUE # Automatically wrap non-wrapped text blocks in paragraph blocks.

      Example of disabling all formatting options::

        aloha:
          'format': []
          'table': []
          'link': []
          'list': []
          'alignment': []
          'formatlesspaste':
            'button': FALSE
            'formatlessPasteOption': TRUE

    ``inspector``
      These settings configure the inspector in the Neos UI for the property.

      ``group``
        Identifier of the *inspector group* this property is categorized into in the content editing
        user interface. If none is given, the property is not editable through the property inspector
        of the user interface.

        The value here must reference a groups configured in the ``ui.inspector.groups`` element of the
        node type this property belongs to.

      ``position``
        Position inside the inspector group, small numbers are sorted on top.

      ``editor``
        Name of the JavaScript Editor Class which is instantiated to edit this element in the inspector.

      ``editorOptions``
        A set of options for the given editor

  ``validation``
    A list of validators to use on the property. Below each validator type any options for the validator
    can be given. See below for more information.

Here is one of the standard Neos node types (slightly shortened)::

	'TYPO3.Neos.NodeTypes:Image':
	  superTypes: ['TYPO3.Neos:Content']
	  ui:
	    label: 'Image'
	    icon: 'icon-picture'
	    inspector:
	      groups:
	        image:
	          label: 'Image'
	          position: 5
	  properties:
	    image:
	      type: TYPO3\Media\Domain\Model\ImageVariant
	      ui:
	        label: 'Image'
	        reloadIfChanged: TRUE
	        inspector:
	          group: 'image'
	    alignment:
	      type: string
	      defaultValue: ''
	      ui:
	        label: 'Alignment'
	        reloadIfChanged: TRUE
	        inspector:
	          group: 'image'
	          editor: 'TYPO3.Neos/Inspector/Editors/SelectBoxEditor'
	          editorOptions:
	            placeholder: 'Default'
	            values:
	              '':
	                label: ''
	              center:
	                label: 'Center'
	              left:
	                label: 'Left'
	              right:
	                label: 'Right'
	    alternativeText:
	      type: string
	      ui:
	        label: 'Alternative text'
	        reloadIfChanged: TRUE
	        inspector:
	          group: 'image'
	      validation:
	        'TYPO3.Neos/Validation/StringLengthValidator':
	          minimum: 1
	          maximum: 255
	    hasCaption:
	      type: boolean
	      ui:
	        label: 'Enable caption'
	        reloadIfChanged: TRUE
	        inspector:
	          group: 'image'
	    caption:
	      type: string
	      defaultValue: '<p>Enter caption here</p>'
	      ui:
	        inlineEditable: TRUE


Property Editor Reference
-------------------------

For each property which is defined in ``NodeTypes.yaml``, the editor inside the Neos inspector can be customized
using various options. Here follows the reference for each property type.

Property Type: boolean ``BooleanEditor`` -- Checkbox editor
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

A ``boolean`` value is rendered using a checkbox in the inspector::

    'isActive'
      type: boolean
      ui:
        label: 'is active'
        inspector:
          group: 'document'

Options Reference:
* (no options)

Property Type: string ``TextFieldEditor`` -- Single-line Text Editor (default)
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Example::

    subtitle:
      type: string
      ui:
        label: 'Subtitle'
        inspector:
          group: 'document'
          editorOptions:
            placeholder: 'Enter subtitle here'
            maxlength: 20

Options Reference:

* ``placeholder`` (string): HTML5 ``placeholder`` property, which is shown if the text field is empty.
* ``disabled`` (boolean): HTML ``disabled`` property. If ``TRUE``, disable this textfield.
* ``maxlength`` (integer): HTML ``maxlength`` property. Maximum number of characters allowed to be entered.
* ``readonly`` (boolean): HTML ``readonly`` property. If ``TRUE``, this field is cannot be written to.
* ``form`` (optional): HTML5 ``form`` property.
* ``selectionDirection`` (optional): HTML5 ``selectionDirection`` property.
* ``spellcheck`` (optional): HTML5 ``spellcheck`` property.
* ``required`` (boolean): HTML5 ``required`` property. If ``TRUE``, input is required.
* ``title`` (boolean): HTML ``title`` property.
* ``autocapitalize`` (boolean): Custom HTML ``autocapitalize`` property.
* ``autocorrect`` (boolean): Custom HTML ``autocorrect`` property.

Property Type: string ``TextAreaEditor`` -- Multi-line Text Editor
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In case the text input should span multiple lines, a ``TextAreaEditor`` should be used as follows::

    'description':
        type: 'string'
        ui:
          label: 'Description'
          inspector:
            group: 'document'
            editor: 'TYPO3.Neos/Inspector/Editors/TextAreaEditor'
            editorOptions:
              rows: 7

Options Reference:

* **all options from Text Field Editor -- see above**
* ``rows`` (integer): Number of lines this textarea should have; Default ``5``.

Property Type: string ``CodeEditor`` -- Full-Screen Code Editor
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In case a lot of space is needed for the text (f.e. for HTML source code), a ``CodeEditor`` can be used::

    'source':
        type: 'string'
        ui:
          label: 'Source'
          inspector:
            group: 'document'
            editor: 'TYPO3.Neos/Inspector/Editors/CodeEditor'

Furthermore, the button label can be adjusted by specifying ``buttonLabel``. Furthermore, the highlighting mode
can be customized, which is helpful for editing markdown and similar contents::

    'markdown':
        type: 'string'
        ui:
          label: 'Markdown'
          inspector:
            group: 'document'
            editor: 'TYPO3.Neos/Inspector/Editors/CodeEditor'
            editorOptions:
              buttonLabel: 'Edit Markdown'
              highlightingMode: 'text/plain'

Options Reference:

* ``buttonLabel`` (string): label of the button which is used to open the full-screen editor. Default ``Edit code``.
* ``highlightingMode`` (string): CodeMirror highlighting mode to use. These formats are support by default:
  ``text/plain``, ``text/xml``, ``text/html``, ``text/css``, ``text/javascript``. If other highlighting modes shall be
  used, they must be loaded beforehand using custom JS code. Default ``text/html``.

Property Type: string / array<string> ``SelectBoxEditor`` -- Dropdown Select Editor
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

In case only fixed strings are allowed to be chosen, a select box can be used. Multiple selection is supported as well.
The most important option is called ``values``, containing the choices which can be made. If wanted, an icon can be displayed
for each choice by setting the ``icon`` class appropriately.

Basic Example -- simple select box::

    targetMode:
      type: string
      defaultValue: 'firstChildNode'
      ui:
        label: 'Target mode'
        inspector:
          group: 'document'
          editor: 'TYPO3.Neos/Inspector/Editors/SelectBoxEditor'
          editorOptions:
            values:
              firstChildNode:
                label: 'First child node'
                icon: 'icon-legal'
              parentNode:
                label: 'Parent node'
                icon: 'icon-fire'
              selectedTarget:
                label: 'Selected target'

If the selection list should be grouped, this can be done by setting the ``group`` key of each individual value::

    country:
      type: string
      ui:
        label: 'Country'
        inspector:
          group: 'document'
          editor: 'TYPO3.Neos/Inspector/Editors/SelectBoxEditor'
          editorOptions:
            values:
              italy:
                label: 'Italy'
                group: 'Southern Europe'
              austria:
                label: 'Austria'
                group: 'Central Europe'
              germany:
                label: 'Germany'
                group: 'Central Europe'

Furthermore, multiple selection is also possible, by setting ``multiple`` to ``TRUE``, which is automatically set
for properties of type ``array``. If an empty value is allowed as well, ``allowEmpty`` should be set to ``TRUE`` and
``placeholder`` should be set to a helpful text::

    styleOptions:
      type: array
      ui:
        label: 'Styling Options'
        inspector:
          group: 'document'
          editor: 'TYPO3.Neos/Inspector/Editors/SelectBoxEditor'
          editorOptions:

            # The next line is set automatically for type array
            # multiple: TRUE

            allowEmpty: TRUE
            placeholder: 'Select Styling Options'

            values:
              leftColumn:
                label: 'Show Left Column'
              rightColumn:
                label: 'Show Right Column'

Because selection options shall be fetched from server-side code frequently, the Select Box Editor contains
support for so-called *data sources*, by setting a ``dataSourceIdentifier``, or optionally a ``dataSourceUri``.
This helps to provide data to the editing interface without having to define routes, policies or a controller.::

    questions:
      ui:
        inspector:
          editor: 'Content/Inspector/Editors/SelectBoxEditor'
          editorOptions:
            dataSourceIdentifier: 'questions'
            # alternatively using a custom uri:
            # dataSourceUri: 'custom-route/end-point'

See :ref:`data-sources` for more details.

The output of the data source has to be a JSON formatted array matching the ``values`` option. Make sure you sort by
group first, if using the grouping option.

Example:

.. code-block:: php

	return json_encode(array(
		'key' => array('label' => 'Foo', group => 'A', 'icon' => 'icon-key'),
		'fire' => array('label' => 'Fire', group => 'A', 'icon' => 'icon-fire')
		'legal' => array('label' => 'Legal', group => 'B', 'icon' => 'icon-legal')
	));

Options Reference:

* ``values`` (required array): the list of values which can be chosen from.
	* [valueKey]
		* ``label`` (required string): label of this value.
		* ``group`` (string): group of this value.
		* ``icon`` (string): CSS icon class for this value.
* ``allowEmpty`` (boolean): if TRUE, it is allowed to choose an empty value.
* ``placeholder`` (string): placeholder text which is shown if nothing is selected. Only works if
  ``allowEmpty`` is ``TRUE``. Default ``Choose``.
* ``multiple`` (boolean): If ``TRUE``, multi-selection is allowed. Default ``FALSE``.
* ``dataSourceUri`` (string): If set, this URI will be called for loading the options of the select field.
* ``dataSourceIdentifier`` (string): If set, a server-side data source will be called for loading the
  possible options of the select field.

Property Type: string ``LinkEditor`` -- Link Editor for internal, external and asset links
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If internal links to other nodes, external links or asset links shall be editable at some point, the
``LinkEditor`` can be used to edit a link::

    myLink:
      type: string
        ui:
          inspector:
            editor: 'TYPO3.Neos/Inspector/Editors/LinkEditor'

The searchbox will accept:

* node document titles
* asset titles and tags
* valid URLs
* valid email addresses

By default, links to generic ``TYPO3.Neos:Document`` nodes are allowed; but by setting the ``nodeTypes`` option,
this can be further restricted (like with the ``reference`` editor). Additionally, links to assets can be disabled
by setting ``assets`` to ``FALSE``. Links to external URLs are always possible. If you need a reference towards
only an asset, use the ``asset`` property type; for a reference to another node, use the ``reference`` node type.
Furthermore, the placeholder text can be customized by setting the ``placeholder`` option::


    myExternalLink:
      type: string
        ui:
          inspector:
            group: 'document'
            editor: 'TYPO3.Neos/Inspector/Editors/LinkEditor'
            editorOptions:
              assets: FALSE
              nodeTypes: ['TYPO3.Neos:Shortcut']
              placeholder: 'Paste a link, or type to search for nodes',

Property Type: integer ``TextFieldEditor``
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Example::

    cropAfterCharacters:
      type: integer
      ui:
        label: 'Crop after characters'
        inspector:
          group: 'document'

Options Reference:

* **all TextFieldEditor Options**

Property Type: reference / references ``ReferenceEditor`` / ``ReferencesEditor`` -- Reference Selection Editors
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The most important option for the property type ``reference`` and ``references`` is ``nodeTypes``, which allows to
restrict the type of the target nodes which can be selected in the editor.

Example::

    authors:
      type: references
      ui:
        label: 'Article Authors'
        inspector:
          group: 'document'
          editorOptions:
            nodeTypes: ['My.Website:Author']

Options Reference:

* ``nodeTypes`` (array of strings): List of node types which are allowed to be selected. By default, is set
  to ``TYPO3.Neos:Document``, allowing only to choose other document nodes.
* ``placeholder`` (string): Placeholder text to be shown if nothing is selected
* ``threshold`` (number): Minimum amount of characters which trigger a search

Property Type: date ``DateTimeEditor`` -- Date & Time Selection Editor
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

The most important option for ``date`` properties is the ``format``, which is configured like in PHP, as the following
examples show:

* ``d-m-Y``: ``05-12-2014`` -- allows to set only the date
* ``d-m-Y H:i``: ``05-12-2014 17:07`` -- allows to set date and time
* ``H:i``: ``17:07`` -- allows to set only the time

Example::

    publishingDate:
      type: date
      ui:
        label: 'Publishing Date'
        inspector:
          group: 'document'
          position: 10
          editorOptions:
            format: 'd.m.Y'

Options Reference:

* ``format`` (required string): The date format, a combination of
  y, Y, F, m, M, n, t, d, D, j, l, N, S, w, a, A, g, G, h, H, i, s. Default ``d-m-Y``.

	* year
		* ``y``: A two digit representation of a year - Examples: 99 or 03
		* ``Y``: A full numeric representation of a year, 4 digits - Examples: 1999 or 2003
	* month
		* ``F``: A full textual representation of a month, such as January or March - January through December
		* ``m``: Numeric representation of a month, with leading zeros - 01 through 12
		* ``M``: A short textual representation of a month, three letters - Jan through Dec
		* ``n``: Numeric representation of a month, without leading zeros - 1 through 12
		* ``t``: Number of days in the given month - 28 through 31
	* day
		* ``d``: Day of the month, 2 digits with leading zeros - 01 to 31
		* ``D``: A textual representation of a day, three letters - Mon through Sun
		* ``j``: Day of the month without leading zeros - 1 to 31
		* ``l``: A full textual representation of the day of the week - Sunday through Saturday
		* ``N``: ISO-8601 numeric representation of the day of the week - 1 (for Monday) through 7 (for Sunday)
		* ``S``: English ordinal suffix for the day of the month, 2 characters - st, nd, rd or th.
		* ``w``: Numeric representation of the day of the week - 0 (for Sunday) through 6 (for Saturday)
	* hour
		* ``a``: Lowercase Ante meridiem and Post meridiem - am or pm
		* ``A``: Uppercase Ante meridiem and Post meridiem - AM or PM
		* ``g``: hour without leading zeros - 12-hour format - 1 through 12
		* ``G``: hour without leading zeros - 24-hour format - 0 through 23
		* ``h``: 12-hour format of an hour with leading zeros - 01 through 12
		* ``H``: 24-hour format of an hour with leading zeros - 00 through 23
	* minute
		* ``i``: minutes, 2 digits with leading zeros - 00 to 59
	* second
		* ``s``: seconds, 2 digits with leading zeros - 00 through 59

* ``placeholder``: The placeholder shown when no date is selected

* ``minuteStep``: The granularity on which a time can be selected. Example: If set to ``30``, only half-hour
  increments of time can be chosen. Default ``5`` minutes.

Property Type: image (TYPO3\Media\Domain\Model\ImageInterface) ``ImageEditor`` -- Image Selection/Upload Editor
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

For properties of type ``TYPO3\Media\Domain\Model\ImageInterface``, an image editor is rendered. If you want cropping
and resizing functionality, you need to set ``features.crop`` and ``features.resize`` to ``TRUE``, as in the following
example::

    'teaserImage'
      type: 'TYPO3\Media\Domain\Model\ImageInterface'
      ui:
        label: 'Teaser Image'
        inspector:
          group: 'document'
          editorOptions:
            features:
              crop: TRUE
              resize: TRUE

If cropping is enabled, you might want to enforce a certain aspect ratio, which can be done by setting
``crop.aspectRatio.locked.width`` and ``crop.aspectRatio.locked.height``. In the following example, the
image format must be ``16:9``::

    'teaserImage'
      type: 'TYPO3\Media\Domain\Model\ImageInterface'
      ui:
        label: 'Teaser Image'
        inspector:
          group: 'document'
          editorOptions:
            features:
              crop: TRUE
            crop:
              aspectRatio:
                locked:
                  width: 16
                  height: 9

If not locking the cropping to a specific ratio, a set of predefined ratios can be chosen by the user. Elements can be
added or removed from this list underneath ``crop.aspectRatio.options``. If the aspect ratio of the original image
shall be added to the list, ``crop.aspectRatio.enableOriginal`` must be set to ``TRUE``. If the user should be allowed
to choose a custom aspect ratio, set ``crop.aspectRatio.allowCustom`` to ``TRUE``::

    'teaserImage'
      type: 'TYPO3\Media\Domain\Model\ImageInterface'
      ui:
        label: 'Teaser Image'
        inspector:
          group: 'document'
          editorOptions:
            features:
              crop: TRUE
            crop:
              aspectRatio:
                options:
                  square:
                    width: 1
                    height: 1
                    label: 'Square'
                  fourFive:
                    width: 4
                    height: 5
                  # disable this ratio (if it was defined in a supertype)
                  fiveSeven: ~
                enableOriginal: TRUE
                allowCustom: TRUE

Options Reference:

* ``maximumFileSize``: (string) Set the maximum allowed file size to be uploaded.
  Accepts numeric or formatted string values, e.g. "204800" or "204800b" or "2kb".
  Defaults to the maximum allowed upload size configured in php.ini

* ``features``
	* ``crop`` (boolean): if ``TRUE``, enable image cropping
	* ``resize`` (boolean): if ``TRUE``, enable image resizing

* ``crop``: crop-related options. Only relevant if ``features.crop`` is enabled.
	* ``aspectRatio``
		* ``locked``: Locks the aspect ratio to a specific width/height ratio
			* ``width``: width of the aspect ratio which shall be enforced
			* ``height``: height of the aspect ratio which shall be enforced
		* ``options``: aspect-ratio presets. Only effective if ``locked`` is not set.
			* [presetIdentifier]
				* ``width`` (required integer): the width of the aspect ratio preset
				* ``height`` (required integer): the height of the aspect ratio preset
				* ``label`` (string): a human-readable name of the aspect ratio preset
		* ``enableOriginal``: If ``TRUE``, the image ratio of the original image can be chosen in the selector.
		  Only effective if ``locked`` is not set. Default ``TRUE``.
		* ``allowCustom``: If ``TRUE``, a completely custom image ratio can be chosen. Only effective if ``locked``
		  is not set. Default ``TRUE``.
		* ``defaultOption`` (string): default aspect ratio option to be chosen if no cropping has been applied already.

Property Type: asset (TYPO3\Media\Domain\Model\Asset / array<TYPO3\Media\Domain\Model\Asset>) ``AssetEditor`` -- File Selection Editor
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

If an asset, i.e. ``TYPO3\Media\Domain\Model\Asset``, shall be uploaded or selected, the following configuration
is an example::

    'caseStudyPdf'
      type: 'TYPO3\Media\Domain\Model\Asset'
      ui:
        label: 'Case Study PDF'
        inspector:
          group: 'document'

Conversely, if multiple assets shall be uploaded, use ``array<TYPO3\Media\Domain\Model\Asset>`` as type::

    'caseStudies'
      type: 'array<TYPO3\Media\Domain\Model\Asset>'
      ui:
        label: 'Case Study PDF'
        inspector:
          group: 'document'

Options Reference:
* (no options)

Property Validation
-------------------

The validators that can be assigned to properties in the node type configuration are used on properties
that are edited via the inspector and are applied on the client-side only. The available validators can
be found in the Neos package in ``Resources/Public/JavaScript/Shared/Validation``:

* AlphanumericValidator
* CountValidator
* DateTimeRangeValidator
* DateTimeValidator
* EmailAddressValidator
* FloatValidator
* IntegerValidator
* LabelValidator
* NotEmptyValidator
* NumberRangeValidator
* RegularExpressionValidator
* StringLengthValidator
* StringValidator
* TextValidator
* UuidValidator

The options are in sync with the Flow validators, so feel free to check the Flow documentation for details.

To apply options, just specify them like this::

	someProperty:
	  validation:
	    'TYPO3.Neos/Validation/StringLengthValidator':
	      minimum: 1
	      maximum: 255

Custom Validators
~~~~~~~~~~~~~~~~~

It is possible to register paths into RequireJS (the JavaScript file and module loader used by Neos, see
http://requirejs.org) and by this custom validators into Neos. Validators should be named '<SomeType>Validator',
and can be referenced by ``My.Package/Public/Scripts/Validators/FooValidator`` for example.

Namespaces can be registered like this in *Settings.yaml*::

	TYPO3:
	  Neos:
	    userInterface:
	      requireJsPathMapping:
	        'My.Package/Validation': 'resource://My.Package/Public/Scripts/Validators'

Registering specific validators is also possible like this::

	TYPO3:
	  Neos:
	    userInterface:
	      validators:
	        'My.Package/AlphanumericValidator':
	          path: 'resource://My.Package/Public/Scripts/Validators/FooValidator'

Custom Editors
~~~~~~~~~~~~~~

Like with validators, using custom editors is possible as well. Every dataType has it's default editor set, which
can have options applied like::

	TYPO3:
	  Neos:
	    userInterface:
	      inspector:
	        dataTypes:
	          'string':
	            editor: 'TYPO3.Neos/Editors/TextFieldEditor'
	            editorOptions:
	              placeholder: 'This is a placeholder'

On a property level this can be overridden like::

	TYPO3:
	  Neos:
	    userInterface:
	      inspector:
	        properties:
	          'string':
	            editor: 'My.Package/Editors/TextFieldEditor'
	            editorOptions:
	              placeholder: 'This is my custom placeholder'

Namespaces can be registered like this, as with validators::

	TYPO3:
	  Neos:
	    userInterface:
	      requireJsPathMapping:
	        'My.Package/Editors': 'resource://My.Package/Public/Scripts/Inspector/Editors'

Editors should be named `<SomeType>Editor` and can be referenced by `My.Package/Inspector/Editors/MyCustomEditor`
for example.

Registering specific editors is also possible like this::

	TYPO3:
	  Neos:
	    userInterface:
	      inspector:
	        editors:
	          'TYPO3.Neos/BooleanEditor':
	            path: 'resource://TYPO3.Neos/Public/JavaScript/Content/Inspector/Editors/BooleanEditor'

Predefined Node Types
---------------------

TYPO3 Neos is shipped with a number of node types. It is helpful to know some of
them, as they can be useful elements to extend, and Neos depends on some of them
for proper behavior.

There are a few core node types which are needed by Neos; these are shipped in ``TYPO3.Neos``
directly. All other node types such as Text, Image, ... are shipped inside the ``TYPO3.Neos.NodeTypes``
package.

TYPO3.Neos:Node
~~~~~~~~~~~~~~~

``TYPO3.Neos:Node`` is a (more or less internal) base type which should be extended by
all content types which are used in the context of TYPO3 Neos.

It does not define any properties.


TYPO3.Neos:Document
~~~~~~~~~~~~~~~~~~~

An important distinction is between nodes which look and behave like pages
and "normal content" such as text, which is rendered inside a page. Nodes which
behave like pages are called *Document Nodes* in Neos. This means they have a unique,
externally visible URL by which they can be rendered.

The standard *page* in Neos is implemented by ``TYPO3.Neos.NodeTypes:Page`` which directly extends from
``TYPO3.Neos:Document``.


TYPO3.Neos:ContentCollection and TYPO3.Neos:Content
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

All content which does not behave like pages, but which lives inside them, is
implemented by two different node types:

First, there is the ``TYPO3.Neos:ContentCollection`` type: A ``TYPO3.Neos:ContentCollection`` has a structural purpose.
It usually does not contain any properties itself, but it contains an ordered list of child
nodes which are rendered inside.

Currently, ``TYPO3.Neos:ContentCollection`` should not be extended by custom types.

Second, the node type for all standard elements (such as text, image, youtube,
...) is ``TYPO3.Neos:Content``. This is–by far–the most often extended node type.
