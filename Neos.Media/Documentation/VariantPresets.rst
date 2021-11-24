=====================
Variant Presets
=====================

Introduction
------------

Neos Media provides a way to automatically generate variants of an original, based on configuration. This allows
for creating images with different aspect ratios, or other adjustments, without further action of an editor.

For example, you may want to generate a square and a wide variant of a given original image in order to use them on
your website. Using Fusion, you can access a specific variant generated through a preset, by referring to the
preset's identifier and the variant name.

This feature is currently in beta, therefore the API and functionality may change in a future release. It is
mainly intended to be combined with further mechanisms, such as automatic image analysis which can set the optimal
clipping for an image crop adjustment.

Variants vs. Thumbnails
-----------------------
The concept of variants and thumbnails are quite similar, but suit different purposes. It's important to know
which concept to choose for a specific use case, therefore let's take a quick look at the differences:

An asset, or more specifically, an image, is either imported, uploaded or created. This is what we also call
the "original asset". The binary data of this asset is exactly the same which was imported in the first place
and it never changes.

An original asset can have any number of variants. Variants are, as the name suggests, variants of an original
which can have one or multiple adjustments applied. For example, there may be a variant which only shows a
part of the image (by using a "crop adjustment") or one which is a black and white version of the original
(using a "grayscale adjustment"). These variants are persistent and can be used like an original asset. The
main difference to an original is that

1. they can be modified
2. they are automatically deleted when the original is deleted

Finally, thumbnails are automatically generated previews of an original asset or a variant. They are used to
make the representation of an asset more suitable for a specific case, for example by resizing an image to
a specific size instead of using the original. Thumbnails are ephemeral, which means that they are created
and destroyed automatically, and not manually by an editor.

Based on these concepts, you should use image variant presets, if you want to automate a task which would
usually been carried out by an editor.

Configuration
-------------

Variant presets are defined in a Settings.yaml of a given package or distribution. Each preset defines one
or more variants to be generated. Each variant can have one or more adjustments automatically applied.

For each preset, one or more media type patterns must be defined. These patterns are regular expressions
which are use to match against the concrete IANA media type of a given asset. The configured variants are
only created when at least one of the media type patterns matches. Note that you need to specify a complete
regular expression, including delimiters ("~" in the example below).

The following example shows the required structure and possible fields of the presets configuration:

.. code-block:: yaml

  Neos:
    Media:
      variantPresets:
        'Flownative.Demo:Preset1':
          label: 'Demo Preset 1'
          mediaTypePatterns: ['~image/(jpe?g|png)~', '~image/vnd\.adobe\.photoshop~']
          variants:
            'wide':
              label: 'Wide'
              description: 'An optional description'
              icon: ''
              adjustments:
                crop:
                  type: 'Neos\Media\Domain\Model\Adjustment\CropImageAdjustment'
                  options:
                    aspectRatio: '16:9'
            'portrait':
              label: 'Portrait'
              description: ''
              icon: ''
              adjustments:
                crop:
                  type: 'Neos\Media\Domain\Model\Adjustment\CropImageAdjustment'
                  options:
                    aspectRatio: '3:4'
            'square':
              label: 'Square'
              description: ''
              icon: ''
              adjustments:
                crop:
                  type: 'Neos\Media\Domain\Model\Adjustment\CropImageAdjustment'
                  options:
                    aspectRatio: '1:1'

The automatic variant generation for new assets has to be enabled via setting as
by default this feature is disabled.

.. code-block:: yaml

  Neos:
    Media:
      autoCreateImageVariantPresets: true

To show and edit the variants in the media module the variants tab has to be enabled.

.. code-block:: yaml

  Neos:
    Media:
      Browser:
        features:
          variantsTab:
            enable: true
