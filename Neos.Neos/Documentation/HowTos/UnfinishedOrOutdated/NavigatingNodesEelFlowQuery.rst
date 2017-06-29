:orphan:

.. Comment

   'orphan' is `file-wide-metadata`_ telling Sphinx, that it should
   not warn that the page is not included in any toctree. Must be at the
   top of this reST code.
   
   _file-wide-metadata: http://sphinx-doc.org/markup/misc.html#file-wide-metadatapage
   
   End of comment.

=======================================
Navigating Nodes with Eel and FlowQuery
=======================================

(Sebastian H.)

Finding the closest node on the rootline having a layout set:

* q(node).parents('[layout]').first()

Get the siblings of a node:

* q(node).siblings()

Get the children of a node:

* q(node).children()
