<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\Domain\Property\Normalizer;

use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final class ArrayNormalizer implements DenormalizerInterface
{
    public function denormalize($data, $type, string $format = null, array $context = [])
    {
        return $data;
    }

    public function supportsDenormalization($data, $type, string $format = null)
    {
        return $type === 'array';
    }
}
