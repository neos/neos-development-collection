<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export\Asset\ValueObject;

enum ImageAdjustmentType: string
{
    case RESIZE_IMAGE = 'RESIZE_IMAGE';
    case CROP_IMAGE = 'CROP_IMAGE';
    case QUALITY_IMAGE = 'QUALITY_IMAGE';

    public function convertProperties(array $data): array
    {
        $data = array_change_key_case($data);
        $convertedData = match ($this) {
            self::RESIZE_IMAGE => [
                'width' => isset($data['width']) ? (int)$data['width'] : null,
                'height' => isset($data['height']) ? (int)$data['height'] : null,
                'maximumWidth' => isset($data['maximumwidth']) ? (int)$data['maximumwidth'] : null,
                'maximumHeight' => isset($data['maximumheight']) ? (int)$data['maximumheight'] : null,
                'minimumWidth' => isset($data['minimumwidth']) ? (int)$data['minimumwidth'] : null,
                'minimumHeight' => isset($data['minimumheight']) ? (int)$data['minimumheight'] : null,
                'ratioMode' => isset($data['ratiomode']) && in_array($data['ratiomode'], ['inset', 'outbound'], true) ? $data['ratiomode'] : null,
                'allowUpScaling' => isset($data['allowupscaling']) ? (bool)$data['allowupscaling'] : null,
                'filter' => isset($data['filter']) ? (string)$data['filter'] : null,
            ],
            self::CROP_IMAGE => [
                'x' => isset($data['x']) ? (int)$data['x'] : null,
                'y' => isset($data['y']) ? (int)$data['y'] : null,
                'width' => isset($data['width']) ? (int)$data['width'] : null,
                'height' => isset($data['height']) ? (int)$data['height'] : null,
                'aspectRatioAsString' => isset($data['aspectratioasstring']) ? (string)$data['aspectratioasstring'] : null,
            ],
            self::QUALITY_IMAGE => [
                'quality' => isset($data['quality']) ? (int)$data['quality'] : null,
            ]
        };
        return array_filter($convertedData, static fn ($value) => $value !== null);
    }
}
