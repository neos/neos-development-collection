=================================
Asynchronous Thumbnail Generation
=================================

Introduction
------------

To optimize response times, generation of thumbnails can be done asynchronously. Usage of asynchronous thumbnail
generation is determined in the image view helpers usage with the ``async`` flag. When the flag is used, a link to the
thumbnail controller returned instead of rendering the thumbnail if the thumbnail hasn't already been rendered.
The thumbnail controller takes a thumbnail object, renders it, if not already done, and redirects to the
thumbnail file.

Usage
-----

To use asynchronous thumbnail generation set the ``async`` parameter to ``TRUE`` in the image view helpers,
see :ref:`Media ViewHelper Reference`.

Configuration
-------------

The configuration setting ``Neos.Media.asyncThumbnails`` is used to determine if asynchronous thumbnails are rendered
when creating thumbnails for configured :ref:`Thumbnails Presets`.

The setting is additionally used as the default value for the ``media:createthumbnails`` command,
see :ref:`Media Command Reference`.

Optimization
------------

Since several simultaneous requests for thumbnails can occur, depending on browser and concurrent users, busy servers
can experience performance issues. Therefore it is recommended to configure the server to run the command
``media:renderthumbnails`` often or use a job queue by listening to the ``thumbnailCreated`` signal and calling
``refreshThumbnail`` for the thumbnail in the thumbnail service.

.. tip::

  Configure ``crontab`` to run the render command every minute: ``* * * * * /path/to/site/flow media:renderthumbnails``

  Use ``media:clearthumbnails`` and ``media:createthumbnails`` to refresh thumbnails.
