============
Introduction
============

When your Editors need a preview of an Asset, you need a solution to generate a Thumbnail.

Available Generators
====================

The Neos Media package contains the following generators:

* Document Thumbnail Generator (``DocumentThumbnailGenerator``)

	Can be used to generate a Thumbnail for any document type supported by ``Imagick``.
	By default it's enabled only for PDF, EPS and AI (Illustrator).

* Font Thumbnail Generator (``FontThumbnailGenerator``)

    Can be used to generate a Thumbnail for any font type supported by ``GD``.
    By default it's enabled only for TTF and ODF.

* Icon Thumbnail Generator (``IconThumbnailGenerator``)

    Can be used to return an Icon from any type of Assets, based on the document MIME type.

* Image Thumbnail Generator (``ImageThumbnailGenerator``)

    Can be used to generate a Thumbnail for any image type supported by ``GD``, ``Imagick`` or ``Gmagick``.

* SVG Thumbnail Generator  (``SvgThumbnailGenerator``)

    This Generator is a special case of the Image Generator to handle SVG, Scalable Vector Graphic.
