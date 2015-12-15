=============
Configuration
=============

How to configure Generator Priority
===================================

In some cases, you need to replace the current Generator by your own implementation or for exemple to replace
the PDF Thumbnail Generator by the Icon Generator for a specific project.

You can do that by configuring each Generator priority.

Change the priority of an existing Generator
--------------------------------------------

You can change the priority (higher is better) for an existing Generator, by editing you ``Settings.yaml``::

    TYPO3:
      Media:
        thumbnailGenerators:
          'TYPO3\Media\Domain\Model\ThumbnailGenerator\DocumentThumbnailGenerator':
            priority: 100

Specific configuration
======================

Check ``Settings.yaml`` in the Media package to see the available configurations by Generator::

    'TYPO3\Media\Domain\Model\ThumbnailGenerator\DocumentThumbnailGenerator':
        resolution: 120
        supportedExtensions: [ 'pdf', 'eps', 'ai' ]
        paginableDocuments: [ 'pdf' ]
