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
	  superTypes:
	    'TYPO3.Neos:Content': true
	  ui:
	    label: 'Special Headline'
	    group: 'general'
	  properties:
	    headline:
	      type: 'string'
	      defaultValue: 'My Headline Default'
	      ui:
	        inlineEditable: true
	      validation:
	        'TYPO3.Neos/Validation/StringLengthValidator':
	          minimum: 1
	          maximum: 255

The following options are allowed:

``abstract``
  A boolean flag, marking a node type as *abstract*. Abstract node types can never be used standalone,
  they will never be offered for insertion to the user in the UI, for example.

  Abstract node types are useful when using inheritance and composition, so mark base node types and
  mixins as abstract.

``aggregate``
  A boolean flag, marking a node type as *aggregate*. If a node type is marked as aggregate, it means that:

  - the node type can "live on its own", i.e. can be part of an external URL
  - when moving this node, all node variants are also moved (across all dimensions)
  - Recursive copying only happens *inside* this aggregate, and stops at nested aggregates.

  The most prominent *aggregate* is `TYPO3.Neos:Document` and everything which inherits from it, like
  `TYPO3.Neos.NodeTypes:Page`.

``superTypes``
  An array of parent node types inherited from as keys with a boolean values.::

    'TYPO3.Neos:Document':
      superTypes:
        'Acme.Demo.ExtraMixin': true

    'TYPO3.Neos:Shortcut':
      superTypes:
        'Acme.Demo.ExtraMixin': false


``constraints``
  Constraint definitions stating which nested child node types are allowed. Also see the dedicated chapter
  :ref:`node-constraints` for detailed explanation::

    constraints:
      nodeTypes:
        # ALLOW text, DISALLOW Image
        'TYPO3.Neos.NodeTypes:Text': true
        'TYPO3.Neos.NodeTypes:Image': false
        # DISALLOW as Fallback (for not-explicitely-listed node types)
        '*': false

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
            'TYPO3.Neos.NodeTypes:Image': true
            '*': false

  By using ``position``, it is possible to define the order in which child nodes appear in the structure tree.
  An example may look like::

    'TYPO3.Neos.NodeTypes:Page':
      childNodes:
        'someChild':
          type: 'TYPO3.Neos:ContentCollection'
          position: 'before main'

  This adds a new ContentCollection called someChild to the default page.
  It will be positioned before the main ContentCollection that the default page has.
  The position setting follows the same sorting logic used in TypoScript
  (see the :ref:`neos-typoscript-reference`).

``label``
  When displaying a node inside the Neos UI (e.g. tree view, link editor, workspace module) the ``label`` option will
  be used to generate a human readable text for a specific node instance (in contrast to the ``ui.label``
  which is used for all nodes of that type).

  The label option accepts an Eel expression that has access to the current node using the ``node`` context variable.
  It is recommended to customize the `label` option for node types that do not yield a sufficient description
  using the default configuration.

  Example::

    'Neos.Demo:Flickr':
      label: ${'Flickr plugin (' + q(node).property('tags') + ')'}

  ``generatorClass``
    Alternatively the class of a node label generator implementing
    ``TYPO3\TYPO3CR\Domain\Model\NodeLabelGeneratorInterface`` can be specified as a nested option.

``ui``
  Configuration options related to the user interface representation of the node type

  ``label``
    The human-readable label of the node type

  ``group``
    Name of the group this content element is grouped into for the 'New Content Element' dialog.
    It can only be created through the user interface if ``group`` is defined and it is valid.

    All valid groups are given in the ``TYPO3.Neos.nodeTypes.groups`` setting

  ``position``
    Position inside the group this content element is grouped into for the 'New Content Element' dialog.
    Small numbers are sorted on top.

  ``icon``
    This setting define the icon to use in the Neos UI for the node type

    Currently it's only possible to use a predefined selection of icons, which
    are available in Font Awesome http://fortawesome.github.io/Font-Awesome/3.2.1/icons/.

  ``help``
    Configuration of contextual help. Displays a message that is rendered as popover
    when the user clicks the help icon in an insert node dialog.

    ``message``
      Help text for the node type. It supports markdown to format the help text and can
      be translated (see :ref:`translate-nodetypes`).

    ``thumbnail``
      This is shown in the popover and can be supplied in two ways:

      - as an absolute URL to an image (``http://static/acme.com/thumbnails/bar.png``)
      - as a resource URI (``resource://AcmeCom.Website/NodeTypes/Thumbnails/foo.png``)

      If the ``thumbnail`` setting is undefined but an image matching the nodetype name
       is found, it will be used automatically. It will be looked for in
       ``<packageKey>/Resources/Public/Images/NodeTypes/<nodeTypeName>.png`` with
       ``packageKey`` and ``nodeTypeName`` being extracted from the full nodetype name
       like this:

       ``AcmeCom.Website:FooWithBar`` -> ``AcmeCom.Website`` and ``FooWithBar``

       The image will be downscaled to a width of 342 pixels, so it should either be that
       size to be placed above any further help text (if supplied) or be half that size for
       the help text to flow around it.

  ``inlineEditable``
    If `true`, it is possible to interact with this Node directly in the content view.
    If `false`, an overlay is shown preventing any interaction with the node.
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

      ``icon``
        This setting define the icon to use in the Neos UI for the group

      ``tab``
        The tab the group belongs to. If left empty the group is added to the ``default`` tab.

      ``collapsed``
        If the group should be collapsed by default (true or false). If left empty, the group will be expanded.

``properties``
  A list of named properties for this node type. For each property the following settings are available.

  .. note:: Your own property names should never start with an underscore ``_`` as that is used for internal
            properties or as an internal prefix.

  ``type``
    Data type of this property. This may be a simple type (like in PHP), a fully qualified PHP class name, or one of
    these three special types: ``DateTime``, ``references``, or ``reference``. Use ``DateTime`` to store dates / time as a
    DateTime object. Use ``reference`` and ``references`` to store references that point to other nodes. ``reference``
    only accepts a single node or node identifier, while ``references`` accepts an array of nodes or node identifiers.

  ``defaultValue``
    Default value of this property. Used at node creation time. Type must match specified 'type'.

  ``ui``
    Configuration options related to the user interface representation of the property

    ``label``
      The human-readable label of the property

    ``help``
      Configuration of contextual help. Displays a message that is rendered as popover
      when the user clicks the help icon in the inspector.

      ``message``
        Help text for this property. It supports markdown to format the help text and can
        be translated (see :ref:`translate-nodetypes`).

    ``reloadIfChanged``
      If `true`, the whole content element needs to be re-rendered on the server side if the value
      changes. This only works for properties which are displayed inside the property inspector,
      i.e. for properties which have a ``group`` set.

    ``reloadPageIfChanged``
      If `true`, the whole page needs to be re-rendered on the server side if the value
      changes. This only works for properties which are displayed inside the property inspector,
      i.e. for properties which have a ``group`` set.

    ``inlineEditable``
      If `true`, this property is inline editable, i.e. edited directly on the page through Aloha.

    ``aloha``
      This section controls the text formatting options the user has available for this property.
      Example::

        aloha:
          'format': # Enable specific formatting options.
            'strong': true
            'b': false
            'em': true
            'i': false
            'u': true
            'sub': true
            'sup': true
            'p': true
            'h1': true
            'h2': true
            'h3': true
            'h4': false
            'h5': false
            'h6': false
            'code': false
            'removeFormat': true
          'table':
            'table': true
          'link':
            'a': true
          'list':
            'ul': true
            'ol': true
          'alignment':
            'left': true
            'center': true
            'right': true
            'justify': true
          'formatlesspaste':
            # Show toggle button for formatless pasting.
            'button': true
            # Whether the format less pasting should be enable by default.
            'formatlessPasteOption': false
            # If not set the default setting is used: 'a', 'abbr', 'b', 'bdi', 'bdo', 'cite', 'code', 'del', 'dfn',
            # 'em', 'i', 'ins', 'kbd', 'mark', 'q', 'rp', 'rt', 'ruby', 's', 'samp', 'small', 'strong', 'sub', 'sup',
            # 'time', 'u', 'var'
            'strippedElements': ['a']
          'autoparagraph': true # Automatically wrap non-wrapped text blocks in paragraph blocks.

      Example of disabling all formatting options::

        aloha:
          'format': []
          'table': []
          'link': []
          'list': []
          'alignment': []
          'formatlesspaste':
            'button': false
            'formatlessPasteOption': true

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
        A set of options for the given editor, see the :ref:`property-editor-reference`.

      ``editorListeners``
        Allows to observe changes of other properties in order to react to them. For details see :ref:`depending-properties`

  ``validation``
    A list of validators to use on the property. Below each validator type any options for the validator
    can be given. See below for more information.

.. tip:: Unset a property by setting the property configuration to null (``~``).

Here is one of the standard Neos node types (slightly shortened)::

	'TYPO3.Neos.NodeTypes:Image':
	  superTypes:
	    'TYPO3.Neos:Content': true
	  ui:
	    label: 'Image'
	    icon: 'icon-picture'
	    inspector:
	      groups:
	        image:
	          label: 'Image'
	          icon: 'icon-image'
	          position: 5
	  properties:
	    image:
	      type: TYPO3\Media\Domain\Model\ImageInterface
	      ui:
	        label: 'Image'
	        reloadIfChanged: true
	        inspector:
	          group: 'image'
	    alignment:
	      type: string
	      defaultValue: ''
	      ui:
	        label: 'Alignment'
	        reloadIfChanged: true
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
	        reloadIfChanged: true
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
	        reloadIfChanged: true
	        inspector:
	          group: 'image'
	    caption:
	      type: string
	      defaultValue: '<p>Enter caption here</p>'
	      ui:
	        inlineEditable: true


