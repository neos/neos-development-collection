<?php
namespace Neos\ContentRepositoryRegistry\Exception;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Core\Factory\ContentRepositoryIdentifier;

#[Flow\Proxy(false)]
final class ContentRepositoryNotFound extends \InvalidArgumentException
{

    public static function notConfigured(ContentRepositoryIdentifier $contentRepositoryIdentifier): self
    {
        return new self(sprintf('A content repository with id "%s" is not configured', $contentRepositoryIdentifier->value), 1650557155);
    }
}
