.. _content-structure:

=================
Content Structure
=================

Before we can understand how content is rendered, we have to see how it is structured
and organized. These basics are explained in this section.

Nodes inside the Neos Content Repository
=========================================

The content in Neos is stored not inside tables of a relational database, but
inside a *tree-based* structure: the so-called Neos Content Repository.

To a certain extent, it is comparable to files in a file-system: They are also
structured as a tree, and are identified uniquely by the complete path towards
the file.

.. note:: Internally, the Neos ContentRepository currently stores the nodes inside database
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
of the pages is represented by a Neos ContentRepository Node of type ``Document``. However, not only
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

	Basically, Neos generalizes the tree-based concept found in TYPO3 CMS
	and TemplaVoila and implements it in a consistent manner, where we do not
	have to distinguish between pages and other content.


Predefined Node Types
---------------------

Neos is shipped with a number of node types. It is helpful to know some of
them, as they can be useful elements to extend, and Neos depends on some of them
for proper behavior.

There are a few core node types which are needed by Neos; these are shipped in ``Neos.Neos``
directly. All other node types such as Text, Image, ... are shipped inside the ``Neos.NodeTypes``
package.

Neos.Neos:Node
~~~~~~~~~~~~~~~

``Neos.Neos:Node`` is a (more or less internal) base type which should be extended by
all content types which are used in the context of Neos.

It does not define any properties.


Neos.Neos:Document
~~~~~~~~~~~~~~~~~~~

An important distinction is between nodes which look and behave like pages
and "normal content" such as text, which is rendered inside a page. Nodes which
behave like pages are called *Document Nodes* in Neos. This means they have a unique,
externally visible URL by which they can be rendered.

The standard *page* in Neos is implemented by ``Neos.NodeTypes:Page`` which directly extends from
``Neos.Neos:Document``.


Neos.Neos:ContentCollection and Neos.Neos:Content
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

All content which does not behave like pages, but which lives inside them, is
implemented by two different node types:

First, there is the ``Neos.Neos:ContentCollection`` type: A ``Neos.Neos:ContentCollection`` has a structural purpose.
It usually contains an ordered list of child nodes which are rendered inside.

``Neos.Neos:ContentCollection`` may be extended by custom types.

Second, the node type for all standard elements (such as text, image, youtube,
...) is ``Neos.Neos:Content``. This is–by far–the most often extended node type.


Extending the NodeTypes
~~~~~~~~~~~~~~~~~~~~~~~

To extend the existing NodeTypes or to create new ones please read at the :ref:`node-type-definition` reference.
