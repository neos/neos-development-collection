<?php

namespace Neos\ContentRepository\Export\Exception;

use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final class LiveWorkspaceContentStreamExistsException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct("Your content repository already contains a live workspace. Please use ./flow cr:prune <content-repository-identifier> to clear the regarding content repository before you import a one.", 1681232587);
    }
}