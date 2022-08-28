<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Infrastructure\Property\Normalizer;

use GuzzleHttp\Psr7\Uri;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * @api these normalizers are used for property serialization; and you can rely on their presence
 */
final class UriNormalizer implements NormalizerInterface, DenormalizerInterface
{
    /**
     * @param array<string,mixed> $context
     */
    public function normalize($data, string $format = null, array $context = []): string
    {
        return (string)$data;
    }

    public function supportsNormalization($data, string $format = null)
    {
        return $data instanceof Uri;
    }

    /**
     * @param array<string,mixed> $context
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        return new Uri($data);
    }

    public function supportsDenormalization($data, string $type, string $format = null)
    {
        return is_string($data) && $type === Uri::class;
    }
}
