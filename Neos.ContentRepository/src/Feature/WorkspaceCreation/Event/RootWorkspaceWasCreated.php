<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\WorkspaceCreation\Event;

use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceTitle;
use Neos\ContentRepository\EventStore\EventInterface;

final class RootWorkspaceWasCreated implements EventInterface
{
    public function __construct(
        public readonly WorkspaceName $workspaceName,
        public readonly WorkspaceTitle $workspaceTitle,
        public readonly WorkspaceDescription $workspaceDescription,
        public readonly UserIdentifier $initiatingUserIdentifier,
        public readonly ContentStreamIdentifier $newContentStreamIdentifier
    ) {
    }

    public static function fromArray(array $values): EventInterface
    {
        return new self(
            WorkspaceName::fromString($values['workspaceName']),
            WorkspaceTitle::fromString($values['workspaceTitle']),
            WorkspaceDescription::fromString($values['workspaceDescription']),
            UserIdentifier::fromString($values['initiatingUserIdentifier']),
            ContentStreamIdentifier::fromString($values['newContentStreamIdentifier']),
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'workspaceName' => $this->workspaceName,
            'workspaceTitle' => $this->workspaceTitle,
            'workspaceDescription' => $this->workspaceDescription,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier,
            'newContentStreamIdentifier' => $this->newContentStreamIdentifier,
        ];
    }
}
