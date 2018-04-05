<?php
namespace Neos\Media\Domain\Model;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
  *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations\Entity;
use Neos\Flow\Annotations\Identity;

/**
 * @Entity
 */
final class ImportedAsset
{
    /**
     * @Identity()
     * @var string
     */
    protected $assetSourceIdentifier;

    /**
     * @Identity()
     * @var string
     */
    protected $remoteAssetIdentifier;

    /**
     * @Identity()
     * @var string
     */
    protected $localAssetIdentifier;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    protected $localOriginalAssetIdentifier = null;

    /**
     * @var \DateTimeImmutable
     */
    protected $importedAt;

    /**
     * @param string $assetSourceIdentifier
     * @param string $remoteAssetIdentifier
     * @param string $localAssetIdentifier
     * @param string $localOriginalAssetIdentifier
     * @param \DateTimeImmutable $importedAt
     */
    public function __construct(
        string $assetSourceIdentifier,
        string $remoteAssetIdentifier,
        string $localAssetIdentifier,
        string $localOriginalAssetIdentifier = null,
        \DateTimeImmutable $importedAt
    ) {
        $this->assetSourceIdentifier = $assetSourceIdentifier;
        $this->remoteAssetIdentifier = $remoteAssetIdentifier;
        $this->localAssetIdentifier = $localAssetIdentifier;
        $this->localOriginalAssetIdentifier = $localOriginalAssetIdentifier;
        $this->importedAt = $importedAt;
    }

    /**
     * @return string
     */
    public function getAssetSourceIdentifier(): string
    {
        return $this->assetSourceIdentifier;
    }

    /**
     * @param string $assetSourceIdentifier
     */
    public function setAssetSourceIdentifier(string $assetSourceIdentifier): void
    {
        $this->assetSourceIdentifier = $assetSourceIdentifier;
    }

    /**
     * @return string
     */
    public function getRemoteAssetIdentifier(): string
    {
        return $this->remoteAssetIdentifier;
    }

    /**
     * @param string $remoteAssetIdentifier
     */
    public function setRemoteAssetIdentifier(string $remoteAssetIdentifier): void
    {
        $this->remoteAssetIdentifier = $remoteAssetIdentifier;
    }

    /**
     * @return string
     */
    public function getLocalAssetIdentifier(): string
    {
        return $this->localAssetIdentifier;
    }

    /**
     * @param string $localAssetIdentifier
     */
    public function setLocalAssetIdentifier(string $localAssetIdentifier): void
    {
        $this->localAssetIdentifier = $localAssetIdentifier;
    }

    /**
     * @return string
     */
    public function getLocalOriginalAssetIdentifier(): ?string
    {
        return $this->localOriginalAssetIdentifier;
    }

    /**
     * @param string $localOriginalAssetIdentifier
     */
    public function setLocalOriginalAssetIdentifier(string $localOriginalAssetIdentifier): void
    {
        $this->localOriginalAssetIdentifier = $localOriginalAssetIdentifier;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getImportedAt(): \DateTimeImmutable
    {
        return $this->importedAt;
    }

    /**
     * @param \DateTimeImmutable $importedAt
     */
    public function setImportedAt(\DateTimeImmutable $importedAt): void
    {
        $this->importedAt = $importedAt;
    }
}
