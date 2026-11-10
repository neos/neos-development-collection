=============
Configuration
=============

How to configure Generator Priority
===================================

In some cases, you might need to replace the current Generator with an alternative implementation. For example, you may want to replace
the PDF Thumbnail Generator with the Icon Generator.

You may achieve this by configuring each Generator's priority.

Change the priority of an existing Generator
--------------------------------------------

You may change the priority (highest takes precedence) for an existing Generator, by editing your ``Settings.yaml``::

    Neos:
      Media:
        thumbnailGenerators:
          'Neos\Media\Domain\Model\ThumbnailGenerator\DocumentThumbnailGenerator':
            priority: 100

Disabling an existing Generator
-------------------------------

To disable an existing Generator use the ``disable`` configuration option for the desired Generator::

    Neos:
      Media:
        thumbnailGenerators:
          'Neos\Media\Domain\Model\ThumbnailGenerator\IconThumbnailGenerator':
            disable: true

Processing assets the size shortcut would skip
----------------------------------------------

When a requested thumbnail would be identical to the original asset (same dimensions, no quality
or format change), no thumbnail is created and the original asset is returned, so no Generator runs.
Generators that do more than resizing — watermarking, image optimisation, provenance markings —
can opt in to process such assets as well::

    Neos:
      Media:
        thumbnailGenerators:
          'Acme\Site\ThumbnailGenerator\WatermarkThumbnailGenerator':
            processUnmodifiedAssets: true

If any enabled Generator has this option set, a thumbnail is created and rendered through the
regular Generator chain even when it would otherwise equal the original.

Specific configuration
======================

Check ``Settings.yaml`` in the Media package to see the available configurations by Generator::

    'Neos\Media\Domain\Model\ThumbnailGenerator\DocumentThumbnailGenerator':
        resolution: 120
        supportedExtensions: [ 'pdf', 'eps', 'ai' ]
        paginableDocuments: [ 'pdf' ]
