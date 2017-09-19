<?php

namespace Neos\ContentRepository\Domain\Context\ContentStream\Event;

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\ContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcing\Event\EventInterface;

/**
 * ContentStreamWasCreated
 */
final class ContentStreamWasCreated implements EventInterface
{
    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var WorkspaceName
     */
    private $workspaceName;

    /**
     * @var UserIdentifier
     */
    private $initiatingUserIdentifier;

    /**
     * ContentStreamWasCreated constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param WorkspaceName $workspaceName
     * @param UserIdentifier $initiatingUserIdentifier
     */
    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, WorkspaceName $workspaceName, UserIdentifier $initiatingUserIdentifier)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->workspaceName = $workspaceName;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return WorkspaceName
     */
    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    /**
     * @return UserIdentifier
     */
    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }
}
