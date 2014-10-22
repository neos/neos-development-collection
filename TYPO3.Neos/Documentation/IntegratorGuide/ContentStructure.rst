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
  An array of parent node types inherited from

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
    are available in Font Awesome http://fortawesome.github.io/Font-Awesome/icons/.

  ``inlineEditable``
    If TRUE, it is possible to interact with this Node directly in the content view.
    If FALSE, an overlay is shown preventing any interaction with the node.
    If not given, checks if any property is marked as ``ui.inlineEditable``.

  ``inspector``
    These settings configure the inspector in the Neos UI for the node type

    ``groups``
      Defines an inspector group that can be used to group properties of the node later.

      ``label``
        The human-readable label for this Inspector Group.

      ``position``
        Position of the inspector group, small numbers are sorted on top

``properties``
  A list of named properties for this node type. For each property the following settings are available.

  ``type``
    Data type of this property. This may be a simple type (like in PHP), a fully qualified PHP class name, or one of
    these three special types: ``date``, ``references``, or ``reference``. Use ``date`` to store dates / time as a DateTime object.
    Use ``reference`` and ``references`` to store references that point to other nodes. ``reference`` only accepts a single node
    or node identifier, while ``references`` accepts an array of nodes or node identifiers.

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
            'b': TRUE
            'i': TRUE
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
#           'strippedElements': ['a'] # If not set the default value is used.

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
    can be given.

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
