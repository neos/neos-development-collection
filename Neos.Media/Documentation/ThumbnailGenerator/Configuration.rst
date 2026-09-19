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

Specific configuration
======================

Check ``Settings.yaml`` in the Media package to see the available configurations by Generator::

    'Neos\Media\Domain\Model\ThumbnailGenerator\DocumentThumbnailGenerator':
        resolution: 120
        supportedExtensions: [ 'pdf', 'eps', 'ai' ]
        paginableDocuments: [ 'pdf' ]
