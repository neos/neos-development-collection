<?php
namespace Neos\ContentRepositoryRegistry\Exception;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final class ContentRepositoryIdNotFoundException extends \InvalidArgumentException
{

    public static function notFound(): self
    {
        return new self('No ContentRepositoryId found for given ContentRepository', 1684865162);
    }
}
