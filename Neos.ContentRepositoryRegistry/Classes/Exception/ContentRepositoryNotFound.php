<?php
namespace Neos\ContentRepositoryRegistry\Exception;

use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;

final class ContentRepositoryNotFound extends \InvalidArgumentException
{

    public static function notConfigured(ContentRepositoryIdentifier $contentRepositoryIdentifier): self
    {
        return new self(sprintf('A content repository with id "%s" is not configured', $contentRepositoryIdentifier->value), 1650557155);
    }
}
