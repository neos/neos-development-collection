<?php
namespace Neos\ContentRepository\Domain\Context\ContentStream\Event;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\ContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcing\Event\EventInterface;

final class ContentStreamWasCreated implements EventInterface
{
    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var UserIdentifier
     */
    private $initiatingUserIdentifier;

    /**
     * ContentStreamWasCreated constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param UserIdentifier $initiatingUserIdentifier
     */
    public function __construct(ContentStreamIdentifier $contentStreamIdentifier, UserIdentifier $initiatingUserIdentifier)
    {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
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
     * @return UserIdentifier
     */
    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }
}
