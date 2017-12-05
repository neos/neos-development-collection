.. _`Media Command Reference`:

Media Command Reference
=======================

.. note:

  This reference uses ``./flow`` as the command to invoke. If you are on
  Windows, this will probably not work, there you need to use ``flow.bat``
  instead.

The commands in this reference are shown with their full command identifiers.
On your system you can use shorter identifiers, whose availability depends
on the commands available in total (to avoid overlap the shortest possible
identifier is determined during runtime).

To see the shortest possible identifiers on your system as well as further
commands that may be available, use::

  ./flow help

The following reference was automatically generated from code on 2016-06-07


.. _`Media Command Reference: TYPO3.MEDIA`:

Package *TYPO3.MEDIA*
---------------------


.. _`Media Command Reference: TYPO3.MEDIA typo3.media:media:clearthumbnails`:

``typo3.media:media:clearthumbnails``
*************************************

**Remove thumbnails**

Removes all thumbnail objects and their resources. Optional ``preset`` parameter to only remove thumbnails
matching a specific thumbnail preset configuration.



Options
^^^^^^^

``--preset``
  Preset name, if provided only thumbnails matching that preset are cleared





.. _`Media Command Reference: TYPO3.MEDIA typo3.media:media:createthumbnails`:

``typo3.media:media:createthumbnails``
**************************************

**Create thumbnails**

Creates thumbnail images based on the configured thumbnail presets. Optional ``preset`` parameter to only create
thumbnails for a specific thumbnail preset configuration.

Additionally accepts a ``async`` parameter determining if the created thumbnails are generated when created.



Options
^^^^^^^

``--preset``
  Preset name, if not provided thumbnails are created for all presets
``--async``
  Asynchronous generation, if not provided the setting ``Neos.Media.asyncThumbnails`` is used





.. _`Media Command Reference: TYPO3.MEDIA typo3.media:media:importresources`:

``typo3.media:media:importresources``
*************************************

**Import resources to asset management**

This command detects Flow "Resource" objects which are not yet available as "Asset" objects and thus don't appear
in the asset management. The type of the imported asset is determined by the file extension provided by the
Resource object.



Options
^^^^^^^

``--simulate``
  If set, this command will only tell what it would do instead of doing it right away





.. _`Media Command Reference: TYPO3.MEDIA typo3.media:media:renderthumbnails`:

``typo3.media:media:renderthumbnails``
**************************************

**Render ungenerated thumbnails**

Loops over ungenerated thumbnails and renders them. Optional ``limit`` parameter to limit the amount of
thumbnails to be rendered to avoid memory exhaustion.



Options
^^^^^^^

``--limit``
  Limit the amount of thumbnails to be rendered to avoid memory exhaustion





