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

If you are working with CMYK images and don't like to convert them automatically into RGB for any reason, you can deactivate this within your Settings.yaml:

.. code-block:: yaml

    TYPO3:
      Media:
        image:
          defaultOptions:
            convertCMYKToRGB: false #default is true
