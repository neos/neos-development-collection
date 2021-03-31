<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcing\Event\DomainEventInterface;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 */
class WorkspaceRebaseFailed implements DomainEventInterface
{
    private WorkspaceName $workspaceName;

    /**
     * The content stream on which we could not apply the source content stream's commands -- i.e. the "failed" state.
     */
    private ContentStreamIdentifier $candidateContentStreamIdentifier;

    /**
     * The content stream which we tried to rebase
     */
    private ContentStreamIdentifier $sourceContentStreamIdentifier;

    private UserIdentifier $initiatingUserIdentifier;

    private array $errors;

    public function __construct(
        WorkspaceName $workspaceName,
        ContentStreamIdentifier $candidateContentStreamIdentifier,
        ContentStreamIdentifier $sourceContentStreamIdentifier,
        UserIdentifier $initiatingUserIdentifier,
        array $errors
    ) {
        $this->workspaceName = $workspaceName;
        $this->candidateContentStreamIdentifier = $candidateContentStreamIdentifier;
        $this->sourceContentStreamIdentifier = $sourceContentStreamIdentifier;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
        $this->errors = $errors;
    }

    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    public function getCandidateContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->candidateContentStreamIdentifier;
    }

    public function getSourceContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->sourceContentStreamIdentifier;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
