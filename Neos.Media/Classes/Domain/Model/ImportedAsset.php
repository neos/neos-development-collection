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
class ImportedAsset
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
     * @var \DateTimeImmutable
     */
    protected $importedAt;

    /**
     * @ORM\Column(nullable=true)
     * @var string
     */
    protected $localOriginalAssetIdentifier = null;

    /**
     * @param string $assetSourceIdentifier
     * @param string $remoteAssetIdentifier
     * @param string $localAssetIdentifier
     * @param \DateTimeImmutable $importedAt
     * @param string $localOriginalAssetIdentifier
     */
    public function __construct(
        string $assetSourceIdentifier,
        string $remoteAssetIdentifier,
        string $localAssetIdentifier,
        \DateTimeImmutable $importedAt,
        string $localOriginalAssetIdentifier = null
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
     * @return string
     */
    public function getRemoteAssetIdentifier(): string
    {
        return $this->remoteAssetIdentifier;
    }

    /**
     * @return string
     */
    public function getLocalAssetIdentifier(): string
    {
        return $this->localAssetIdentifier;
    }

    /**
     * @return string
     */
    public function getLocalOriginalAssetIdentifier(): ?string
    {
        return $this->localOriginalAssetIdentifier;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getImportedAt(): \DateTimeImmutable
    {
        return $this->importedAt;
    }
}
