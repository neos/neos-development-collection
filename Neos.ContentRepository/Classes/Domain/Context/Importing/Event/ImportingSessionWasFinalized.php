<?php
namespace Neos\ContentRepository\Domain\Context\Importing\Event;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Context\Node\Event\CopyableAcrossContentStreamsInterface;
use Neos\ContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\ValueObject\ImportingSessionIdentifier;
use Neos\EventSourcing\Event\EventInterface;

final class ImportingSessionWasFinalized implements EventInterface, CopyableAcrossContentStreamsInterface
{

    /**
     * @var ImportingSessionIdentifier
     */
    private $importingSessionIdentifier;

    /**
     * @param ImportingSessionIdentifier $importingSessionIdentifier
     */
    public function __construct(ImportingSessionIdentifier $importingSessionIdentifier)
    {
        $this->importingSessionIdentifier = $importingSessionIdentifier;
    }

    /**
     * @return ImportingSessionIdentifier
     */
    public function getImportingSessionIdentifier(): ImportingSessionIdentifier
    {
        return $this->importingSessionIdentifier;
    }

    public function createCopyForContentStream(ContentStreamIdentifier $targetContentStream)
    {
        // nothing to copy here
    }
}
