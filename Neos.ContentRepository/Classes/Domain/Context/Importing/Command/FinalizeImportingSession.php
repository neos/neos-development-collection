<?php
namespace Neos\ContentRepository\Domain\Context\Importing\Command;

use Neos\ContentRepository\Domain\ValueObject\ImportingSessionIdentifier;

final class FinalizeImportingSession
{
    /**
     * @var ImportingSessionIdentifier
     */
    private $importingSessionIdentifier;

    public function __construct(ImportingSessionIdentifier $importingSessionIdentifier)
    {
        $this->importingSessionIdentifier = $importingSessionIdentifier;
    }

    public function getImportingSessionIdentifier(): ImportingSessionIdentifier
    {
        return $this->importingSessionIdentifier;
    }
}
