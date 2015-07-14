.. note::
  This is a documentation stub.

.. _multi-site:

==================
Multi Site Support
==================

Separating Assets Between Sites
===============================

In multi-site setups it can become a use case to having to separate assets to a between sites. For this Neos supports
creating asset collections. An asset collection can contain multiple assets, and an asset can belong to multiple
collections. Additionally tags can belong to one or multiple collections.

Every site can (in the site management module) be configured to have a default asset collection. This means that when
assets are uploaded in the inspector they will automatically be added to the sites collection if one is configured.
When the editor opens the media browser/module it will automatically select the current sites collection.

The media browser/module allows administrators to create/edit/delete collections and also select which tags are
included in a collection.