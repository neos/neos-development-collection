<?php

namespace Neos\ContentRepository\Domain\Context\Node\Command;

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;

final class HideNode
{

    /**
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeIdentifier
     */
    private $nodeIdentifier;

    /**
     * HideNode constructor.
     *
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $nodeIdentifier
     */
    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        NodeIdentifier $nodeIdentifier
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->nodeIdentifier = $nodeIdentifier;
    }

    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return NodeIdentifier
     */
    public function getNodeIdentifier(): NodeIdentifier
    {
        return $this->nodeIdentifier;
    }

}
