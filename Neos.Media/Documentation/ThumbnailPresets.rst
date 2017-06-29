=================
Thumbnail Presets
=================

Introduction
------------

Thumbnail presets allows thumbnails to be easily reused to reduce the amount of rendered thumbnails.

Configuration
-------------

Thumbnails presets are configured using configuration settings in ``Neos.Media.thumbnailPresets``. It accepts the
parameters used in ``ThumbnailConfiguration``, except for the ``async`` parameter.

.. code-block:: yaml

  TYPO3:
    Media:
      thumbnailPresets:
        'Acme.Demo:Thumbnail':
          maximumWidth: 500
          maximumHeight: 500

Optimization
------------

When new assets are uploaded, thumbnails for the configured presets are automatically created, unless disabled using the
configuration setting ``Neos.Media.autoCreateThumbnailPresets``.

If :ref:`Asynchronous Thumbnail Generation` is disabled, the thumbnails will be rendered immediately making
uploading slower.

Utilities
---------

To create or clear thumbnails for configured presets use the ``typo3.media:media:createthumbnails`` and
``typo3.media:media:clearthumbnails`` commands, see :ref:`Media Command Reference`.
