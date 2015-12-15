=================
Thumbnail Presets
=================

Introduction
------------

Thumbnail presets allows thumbnails to be easily reused to reduce the amount of generated thumbnails.

Configuration
-------------

Thumbnails presets are configured using configuration settings in ``TYPO3.Media.thumbnailPresets``. It accepts the
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
configuration setting ``TYPO3.Media.autoGenerateThumbnailPresets``.

If :ref:`Asynchronous Thumbnail Generation` is disabled, the thumbnails will be generated immediately making
uploading slower.

Utilities
---------

To create or clear thumbnails for configured presets use the ``typo3.media:media:createthumbnails`` and
``typo3.media:media:createthumbnails`` commands, see :ref:`Media Command Reference`.