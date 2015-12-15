========
Commands
========

Import resource ``media:importresources``
-----------------------------------------



Create thumbnails ``media:createthumbnails``
--------------------------------------------

To create thumbnails based on configured :ref:`Thumbnail Presets` the command ``media:createthumbnails`` can be used.

By default it creates for all configured presets, unless a specific preset is added using the ``preset`` parameter.

Asynchronous thumbnails are

Optimization
------------

Since several simultaneous requests for thumbnails can occur, depending on browser
and concurrent users, busy servers can experience problems. However it is recommended to
configure the server to run ``media:generatethumbnails`` often or use a job queue by listening to the
``thumbnailCreated`` signal and calling ``refreshThumbnail`` for the thumbnail in the thumbnail service.