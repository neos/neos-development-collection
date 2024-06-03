<?php
namespace Neos\ContentRepositoryRegistry\Exception;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final class ContentRepositoryNotFoundException extends \InvalidArgumentException
{

    public static function notConfigured(ContentRepositoryId $contentRepositoryId): self
    {
        return new self(sprintf('A content repository with id "%s" is not configured', $contentRepositoryId->value), 1650557155);
    }
}
