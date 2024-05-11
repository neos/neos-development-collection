<?php
declare(strict_types=1);

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Utility\ObjectAccess;

/**
 * Step implementations for tests inside Neos.Neos
 *
 * @internal only for behat tests within the Neos.Neos package
 */
trait AssetTrait
{
    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return T
     */
    abstract private function getObject(string $className): object;

    /**
     * @Given an asset exists with id :assetId
     */
    public function anAssetExistsWithId(string $assetId): void
    {
        $resourceManager = $this->getObject(ResourceManager::class);
        $resourceContent = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 111.42 125"><defs><style>.cls-1{fill:#010101;}</style></defs><title>neos_avatar_monochrome</title><g id="Ebene_2" data-name="Ebene 2"><g id="Layer_1" data-name="Layer 1"><path class="cls-1" d="M94.59,125H71.37L44.95,87.27v22.24L23.85,125H0V7.28L9.88,0H36.26L66.47,43V15.49L87.57,0h23.85V112.67ZM2.63,8.61V121.09l17.58-12.91V47.35l52.61,75.12h20.7l12.78-9.32H87.19L10,3.17ZM22.78,122.53l19.54-14.35V83.51L22.84,55.59v53.94l-17.72,13ZM12.85,2.63,88.68,110.68h20.11V2.63H89.32V80.16L34.89,2.63ZM69.11,46.79l17.58,25.1v-68L69.11,16.82Z"/></g></g></svg>';
        $resource = $resourceManager->importResourceFromContent($resourceContent, 'test.svg');
        $asset = new Image($resource);
        ObjectAccess::setProperty($asset, 'Persistence_Object_Identifier', $assetId, true);

        $this->getObject(AssetRepository::class)->add($asset);
        $this->getObject(PersistenceManagerInterface::class)->persistAll();
    }

    /**
     * @Given the asset :assetId has the title :title
     * @Given the asset :assetId has the title :title and caption :caption
     * @Given the asset :assetId has the title :title and caption :caption and copyright notice :copyrightNotice
     */
    public function theAssetHasTheTitleAndCaptionAndCopyrightNotice($assetId, $title, $caption = null, $copyrightNotice = null): void
    {
        $repository = $this->getObject(AssetRepository::class);
        $asset = $repository->findByIdentifier($assetId);

        $asset->setTitle($title);
        $caption && $asset->setCaption($caption);
        $copyrightNotice && $asset->setCopyrightNotice($copyrightNotice);

        $repository->update($asset);
        $this->getObject(PersistenceManagerInterface::class)->persistAll();
    }
}
