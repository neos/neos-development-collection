<?php
namespace Neos\ContentRepositoryRegistry\Exception;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;

final class InvalidConfigurationException extends \RuntimeException
{
    public static function missingPreset(ContentRepositoryId $contentRepositoryId, string $presetName): self
    {
        return new self(sprintf('The preset "%s" referred to in content repository "%s" does not exist', $presetName, $contentRepositoryId->value), 1650557150);
    }

    public static function fromException(ContentRepositoryId $contentRepositoryId, \Exception $exception): self
    {
        return new self(sprintf('Failed to create content repository "%s": %s', $contentRepositoryId->value, $exception->getMessage()), 1650557143, $exception);
    }

    public static function fromMessage(string $message, mixed ...$values): self
    {
        return new self(vsprintf($message, $values), 1651064446);
    }
}
