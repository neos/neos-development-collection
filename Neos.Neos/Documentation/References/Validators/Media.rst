.. _`Media Validator Reference`:

Media Validator Reference
=========================

This reference was automatically generated from code on 2021-12-08


.. _`Media Validator Reference: ImageOrientationValidator`:

ImageOrientationValidator
-------------------------

Validator that checks the orientation (square, portrait, landscape) of a given image.

Supported validator options are (array)allowedOrientations with one or two out of 'square', 'landcape' or 'portrait'.

*Example*::

  [at]Flow\Validate("$image", type="\Neos\Media\Validator\ImageOrientationValidator",
        options={ "allowedOrientations"={"square", "landscape"} })

this would refuse an image that is in portrait orientation, but allow landscape and square ones.

The given $value is valid if it is an \Neos\Media\Domain\Model\ImageInterface of the
configured orientation (square, portrait and/or landscape)
Note: a value of NULL or empty string ('') is considered valid

.. note:: A value of NULL or an empty string ('') is considered valid



Arguments
*********

* ``allowedOrientations`` (array): Array of image orientations, one or two out of 'square', 'landcape' or 'portrait'




.. _`Media Validator Reference: ImageSizeValidator`:

ImageSizeValidator
------------------

Validator that checks size (resolution) of a given image

Example:
[at]Flow\Validate("$image", type="\Neos\Media\Validator\ImageSizeValidator", options={ "minimumWidth"=150, "maximumResolution"=60000 })

The given $value is valid if it is an \Neos\Media\Domain\Model\ImageInterface of the configured resolution
Note: a value of NULL or empty string ('') is considered valid

.. note:: A value of NULL or an empty string ('') is considered valid



Arguments
*********

* ``minimumWidth`` (integer, *optional*): The minimum width of the image

* ``minimumHeight`` (integer, *optional*): The minimum height of the image

* ``maximumWidth`` (integer, *optional*): The maximum width of the image

* ``maximumHeight`` (integer, *optional*): The maximum height of the image

* ``minimumResolution`` (integer, *optional*): The minimum resolution of the image

* ``maximumResolution`` (integer, *optional*): The maximum resolution of the image




.. _`Media Validator Reference: ImageTypeValidator`:

ImageTypeValidator
------------------

Validator that checks the type of a given image

Example:
[at]Flow\Validate("$image", type="\Neos\Media\Validator\ImageTypeValidator", options={ "allowedTypes"={"jpeg", "png"} })

The given $value is valid if it is an \Neos\Media\Domain\Model\ImageInterface of the
configured type (one of the image/* IANA media subtypes)

Note: a value of NULL or empty string ('') is considered valid

.. note:: A value of NULL or an empty string ('') is considered valid



Arguments
*********

* ``allowedTypes`` (array): Allowed image types (using image/* IANA media subtypes)



