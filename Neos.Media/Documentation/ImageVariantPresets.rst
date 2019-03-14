=====================
Image Variant Presets
=====================

Introduction
------------

Neos Media provides a way to automatically generate variants of an original, based on configuration. This allows
for creating images with different aspect ratios, or other adjustments, without further action of an editor.

For example, you may want to generate a square and a wide variant of a given original in order to use them on
your website. Using Fusion, you can access a specific variant generated through a preset, by referring to the
preset's identifier and the variant name.

This feature is currently in beta, therefore the API and functionality may change in a future release. It is
mainly intended to be combined with further mechanisms, such as automatic image analysis which can set the optimal
clipping for an image crop adjustment.

Configuration
-------------

Image variant presets are defined in a Settings.yaml of a given package or distribution. Each preset defines one
or more variants to be generated. Each variant can have one or more adjustments automatically applied.

The following example shows the required structure and possible fields of the presets configuration:

.. code-block:: yaml
```yaml
    imageVariantPresets:
      'Flownative.Demo:Preset1':
        label: 'Demo Preset 1'
        variants:
          'wide':
            label: 'Wide'
            description: 'An optional description'
            icon: ''
            adjustments:
              crop:
                type: 'Neos\Media\Domain\Model\Adjustment\CropImageAdjustment'
                options:
                  aspectRatio:
                    width: 16
                    height: 9
          'lowerRight':
            label: 'Lower Right'
            description: ''
            icon: ''
            adjustments:
              crop:
                type: 'Neos\Media\Domain\Model\Adjustment\CropImageAdjustment'
                options:
                  width: '50%'
                  height: '50%'
          'square':
            label: 'Square'
            description: ''
            icon: ''
            adjustments:
              crop:
                type: 'Neos\Media\Domain\Model\Adjustment\CropImageAdjustment'
                options:
                  aspectRatio:
                    width: 1
                    height: 1
```
