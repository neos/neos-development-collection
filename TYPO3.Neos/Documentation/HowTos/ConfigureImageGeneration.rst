==========================
Configure image generation
==========================

Changing output quality of images
=================================

You can change the output quality of generated images within your Settings.yaml.
Set the `quality` to your preferred value (between 0 - very poor and 100 - very good).

.. code-block:: yaml
    TYPO3:
      Media:
        image:
          defaultOptions:
            'quality': 90


Convert CMYK images into RGB
============================

If you are working with CMYK images and like to convert them automatically for web usage, you can activate this within your Settings.yaml:

.. code-block:: yaml
    TYPO3:
      Media:
        image:
          convertCMYKToRGB: true #default is false