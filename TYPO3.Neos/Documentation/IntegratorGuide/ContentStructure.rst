===========================
The TYPO3 Content Structure
===========================

Before we can understand how content is rendered, we have to see how it is structured
and organized. These basics are explained in this section.

Nodes inside the TYPO3 Content Repository
=========================================

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
=======================

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
