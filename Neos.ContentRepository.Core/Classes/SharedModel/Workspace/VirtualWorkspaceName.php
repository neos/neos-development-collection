<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Workspace;

use Ramsey\Uuid\Uuid;

/**
 * @internal
 */
class VirtualWorkspaceName implements \JsonSerializable
{
    private const VIRTUAL_PREFIX = 'vrt-';

    public readonly string $value;

    private function __construct(
        public readonly ContentStreamId $contentStreamId
    ) {
        $identifierBase64 = base64_encode($this->contentStreamId->value);
        $this->value = self::VIRTUAL_PREFIX . $identifierBase64;
    }

    public static function fromString(string $value): self
    {
        if (!self::isVirtual($value)) {
            throw new \InvalidArgumentException('This is not a virtual workspace name: ' . $value, 1729286719);
        }
        $identifier = base64_decode(substr($value, strlen(self::VIRTUAL_PREFIX)));
        return new self(ContentStreamId::fromString($identifier));
    }

    public static function fromContentStreamId(ContentStreamId $contentStreamId): self
    {
        return new self($contentStreamId);
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public static function isVirtual(string $value): bool
    {
        return str_starts_with($value, self::VIRTUAL_PREFIX);
    }

    public function isLive(): bool
    {
        return false;
    }
}
