<?php
namespace Neos\ContentRepository\Domain\Context\Importing\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ValueObject\ImportingSessionIdentifier;

final class FinalizeImportingSession
{
    /**
     * @var ImportingSessionIdentifier
     */
    private $importingSessionIdentifier;

    /**
     * FinalizeImportingSession constructor.
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
