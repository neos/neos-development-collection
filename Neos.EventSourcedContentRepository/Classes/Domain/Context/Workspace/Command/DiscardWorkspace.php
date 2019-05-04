<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;

/**
 * Discard a workspace's changes
 */
final class DiscardWorkspace
{
    /**
     * @var WorkspaceName
     */
    private $workspaceName;

    /**
     * PublishWorkspace constructor.
     * @param WorkspaceName $workspaceName
     */
    public function __construct(WorkspaceName $workspaceName)
    {
        $this->workspaceName = $workspaceName;
    }

    public static function fromArray(array $array): self
    {
        return new static(
            new WorkspaceName($array['workspaceName'])
        );
    }

    /**
     * @return WorkspaceName
     */
    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }
}
