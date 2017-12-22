<?php
/**
 * Created by IntelliJ IDEA.
 * User: sebastian
 * Date: 22.12.17
 * Time: 08:20
 */

namespace Neos\ContentRepository\Domain\Context\Node\Event;


use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;

interface CopyableAcrossContentStreamsInterface
{
    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStream);
}
