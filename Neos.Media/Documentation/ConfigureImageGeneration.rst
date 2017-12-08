==========================
Configure image generation
==========================

Changing output quality of images
=================================

You can change the output quality of generated images within your Settings.yaml.
Set the `quality` to your preferred value (between 0 - very poor and 100 - very good).

.. code-block:: yaml

    Neos:
      Media:
        image:
          defaultOptions:
            'quality': 90


Convert CMYK images into RGB
============================

If you are working with CMYK images and don't like to convert them automatically into RGB for any reason, you can deactivate this within your Settings.yaml:

.. code-block:: yaml

    Neos:
      Media:
        image:
          defaultOptions:
            convertCMYKToRGB: false #default is true


Changed default filter for Image processing
===========================================

If you have configured a Imagine driver that support alternative filter (this the case is you use Imagick or Gmagick), you can select the filter within your Settings.yaml:

.. code-block:: yaml

    Neos:
      Media:
        image:
          defaultOptions:
            resizeFilter: '%\Imagine\Image\ImageInterface::FILTER_UNDEFINED%'

Unfortunatly Gd does not support other filter than the default one. Good candidate can be FILTER_BOX or FILTER_CATROOM. You can check the documentation of your image driver for
more informations about each filter. Check the \Imagine\Image\ImageInterface to know with filter are supported by Imagine.

Produce interlaced images
=========================

To generate progressive images you can configure the driver within your Settings.yaml:

.. code-block:: yaml

    Neos:
      Media:
        image:
          defaultOptions:
            interlace: '%\Imagine\Image\ImageInterface::INTERLACE_LINE%'

 Check the \Imagine\Image\ImageInterface to know with mode are supported by Imagine.
