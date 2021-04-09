<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\Domain\Property\Normalizer;

use GuzzleHttp\Psr7\Uri;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final class UriNormalizer implements NormalizerInterface, DenormalizerInterface
{
    public function normalize($data, string $format = null, array $context = [])
    {
        return (string)$data;
    }

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof Uri;
    }

    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        return new Uri($data);
    }

    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return is_string($data) && $type === Uri::class;
    }
}
