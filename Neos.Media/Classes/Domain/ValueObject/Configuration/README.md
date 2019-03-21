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
