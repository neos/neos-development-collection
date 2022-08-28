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

namespace Neos\ContentRepository\Feature\WorkspaceRebase\Event;

use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\EventStore\EventInterface;

/**
 * @api events are the persistence-API of the content repository
 */
final class WorkspaceRebaseFailed implements EventInterface
{
    /**
     * @param array<int,array<string,mixed>> $errors
     */
    public function __construct(
        public readonly WorkspaceName $workspaceName,
        /**
         * The content stream on which we could not apply the source content stream's commands -- i.e. the "failed"
         * state.
         */
        public readonly ContentStreamIdentifier $candidateContentStreamIdentifier,
        /**
         * The content stream which we tried to rebase
         */
        public readonly ContentStreamIdentifier $sourceContentStreamIdentifier,
        public readonly UserIdentifier $initiatingUserIdentifier,
        public readonly array $errors
    ) {
    }

    public static function fromArray(array $values): self
    {
        return new self(
            WorkspaceName::fromString($values['workspaceName']),
            ContentStreamIdentifier::fromString($values['candidateContentStreamIdentifier']),
            ContentStreamIdentifier::fromString($values['sourceContentStreamIdentifier']),
            UserIdentifier::fromString($values['initiatingUserIdentifier']),
            $values['errors']
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'workspaceName' => $this->workspaceName,
            'candidateContentStreamIdentifier' => $this->candidateContentStreamIdentifier,
            'sourceContentStreamIdentifier' => $this->sourceContentStreamIdentifier,
            'initiatingUserIdentifier' => $this->initiatingUserIdentifier,
            'errors' => $this->errors
        ];
    }
}
