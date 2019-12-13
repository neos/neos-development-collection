# Configuration Value Objects

This namespace contains classes for value objects which are used to parse
variant presets from settings.

The following example shows the structure of these settings:

```yaml
    variantPresets:
      'Flownative.Demo:Preset1':
        label: 'Demo Preset 1'
        mediaTypePatterns: ['~image/jpe?g~', '~image/png~']
        variants:
          'wide':
            label: 'Wide'
            description: 'An optional description'
            adjustments:
              crop:
                type: 'Neos\Media\Domain\Model\Adjustment\CropImageAdjustment'
                options:
                  aspectRatio: '16:9'
          'square':
            label: 'Square'
            description: ''
            adjustments:
              crop:
                type: 'Neos\Media\Domain\Model\Adjustment\CropImageAdjustment'
                options:
                  aspectRatio: '1:1'
          'verySmallWithOffset':
            label: 'Small with offset'
            description: ''
            adjustments:
              crop:
                type: 'Neos\Media\Domain\Model\Adjustment\CropImageAdjustment'
                options:
                  width: 50
                  height: 75
                  x: 100
                  y: 200
```

The adjustment `type` can be any implementation of `Neos\Media\Domain\Model\Adjustment\AdjustmentInterface`,
any available setter can be given below `options`.
