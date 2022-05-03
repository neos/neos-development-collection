<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\Middleware\Asset\ValueObject;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\PersistentResource;

/**
 * @Flow\Proxy(false)
 */
final class SerializedResource implements \JsonSerializable
{
    private function __construct(
        public readonly string $filename,
        public readonly string $collectionName,
        public readonly string $mediaType,
        public readonly string $sha1,
    ) {}

    public static function fromResource(PersistentResource $resource): self
    {
        return new self(
            $resource->getFilename(),
            $resource->getCollectionName(),
            $resource->getMediaType(),
            $resource->getSha1()
        );
    }

    /**
     * @param array{filename: string, collectionName: string, mediaType: string, sha1: string} $array
     * @return static
     */
    public static function fromArray(array $array): self
    {
        $expectedKeys = ['filename', 'collectionName', 'mediaType', 'sha1'];
        $missingKeys = array_diff($expectedKeys, array_keys($array));
        if ($missingKeys !== []) {
            throw new \InvalidArgumentException(sprintf('The following key%s missing: %s', count($missingKeys) === 1 ? ' is' : 's are', implode(', ', $missingKeys)), 1645873164);
        }
        $unknownKeys = array_diff(array_keys($array), $expectedKeys);
        if ($unknownKeys !== []) {
            throw new \InvalidArgumentException(sprintf('The following key%s unknown: %s', count($unknownKeys) === 1 ? ' is' : 's are', implode(', ', $unknownKeys)), 1645873166);
        }
        return new self(
            $array['filename'],
            $array['collectionName'],
            $array['mediaType'],
            $array['sha1'],
        );
    }

    public function matches(PersistentResource $resource): bool
    {
        return $resource->getFilename() === $this->filename
            && $resource->getCollectionName() === $this->collectionName
            && $resource->getMediaType() === $this->mediaType
            && $resource->getSha1() === $this->sha1;
    }

    /**
     * @return array<mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
