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
	    'TYPO3.Neos:Content': TRUE
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
  An array of parent node types inherited from as keys with a boolean values.::

    'TYPO3.Neos:Document':
      superTypes:
        'Acme.Demo.ExtraMixin': TRUE

    'TYPO3.Neos:Shortcut':
      superTypes:
        'Acme.Demo.ExtraMixin': FALSE


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

    'TYPO3.NeosDemoTypo3Org:Flickr':
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

    ``reloadIfChanged``
      If TRUE, the whole content element needs to be re-rendered on the server side if the value
      changes. This only works for properties which are displayed inside the property inspector,
      i.e. for properties which have a ``group`` set.

    ``reloadPageIfChanged``
      If TRUE, the whole page needs to be re-rendered on the server side if the value
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
        A set of options for the given editor, see the :ref:`property-editor-reference`.

  ``validation``
    A list of validators to use on the property. Below each validator type any options for the validator
    can be given. See below for more information.

.. tip:: Unset a property by setting the property configuration to null (``~``).

Here is one of the standard Neos node types (slightly shortened)::

	'TYPO3.Neos.NodeTypes:Image':
	  superTypes:
	    'TYPO3.Neos:Content': TRUE
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
	      type: TYPO3\Media\Domain\Model\ImageInterface
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


