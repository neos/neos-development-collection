<?php
namespace Neos\ContentRepository\Domain\Context\Importing\Event;

use Neos\ContentRepository\Domain\ValueObject\ImportingSessionIdentifier;
use Neos\EventSourcing\Event\EventInterface;

final class ImportingSessionWasFinalized implements EventInterface
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

}
