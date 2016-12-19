============
Introduction
============

Thumbnail Generators allows previewing different kinds of assets by generating thumbnails for them.

Available Generators
====================

The Neos Media package contains the following generators:

* Document Thumbnail Generator (``DocumentThumbnailGenerator``)

    Generates a Thumbnail for any document type supported by ``Imagick``.
    By default enabled for PDF, EPS and AI (Illustrator).

* Font Thumbnail Generator (``FontThumbnailGenerator``)

    Generates a Thumbnail for any font type supported by ``GD``.
    By default enabled for TTF and ODF.

* Icon Thumbnail Generator (``IconThumbnailGenerator``)

    Returns a static icon image from common types of Assets, based on the asset MIME type.

* Image Thumbnail Generator (``ImageThumbnailGenerator``)

    Generates a Thumbnail for any image types supported by ``GD``, ``Imagick`` or ``Gmagick``.
