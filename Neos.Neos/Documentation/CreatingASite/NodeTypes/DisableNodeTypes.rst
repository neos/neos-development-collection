.. _disable-nodetypes:

Disable NodeTypes
===================

To hide an existing NodeType (i.e. one that comes with Neos already) you have 2 options.

Hide the NodeType from the user interface
=========================================

*NodeTypes.yaml*

.. code-block:: yaml

  Vendor.Site:YourContentElementName:
    ui: ~

Nodes of this type will still remain valid in the database and being rendered to the frontend. But they will not be
shown anymore in the dialog for adding nodes.

Completely disallow the direct usage of a NodeType
==================================================

*NodeTypes.yaml*

.. code-block:: yaml

  Vendor.Site:YourContentElementName:
    abstract: TRUE

As abstract NodeTypes are not valid to be used directly this will hide the NodeType in the user interface AND
additionally make all existing nodes of this type invalid. If you run a node:repair all existing nodes of this type will
be removed.

.. note:: Do not delete the complete NodeType via ~ because this will break all NodeTypes that inherit from this one.
