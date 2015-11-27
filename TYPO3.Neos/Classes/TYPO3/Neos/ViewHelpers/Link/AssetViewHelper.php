<?php
namespace TYPO3\Neos\ViewHelpers\Link;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Repository\AssetRepository;

/**
 * A view helper for creating links to assets
 * Can be any asset, the uri will point to the public _Resources/Persistent folder
 * = Examples =
 * <code title="Asset url string">
 * <neos:link.asset asset="asset://my-asset-identifier">Resource right here</neos:link.asset>
 * </code>
 * <output>
 * <a href="sites/mysite.com/_Resources/Persistent/9/my-image.jpg">Resource right here</a>
 * (depending whether you are referencing a image, video, document etc..)
 * </output>
 * <code title="Asset object">
 * <neos:link.asset asset="{asset}">Resource right here</neos:link.asset>
 * </code>
 * <output>
 * <a href="sites/mysite.com/_Resources/Persistent/9/my-image.jpg">Resource right here</a>
 * (depending whether you are referencing a image, video, document etc..)
 * </output>
 *
 * @api
 */
class AssetViewHelper extends AbstractTagBasedViewHelper
{

    /**
     * @var string
     */
    protected $tagName = 'a';

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @param AssetInterface|string $asset
     * @param string|null $target if null the target attribute is skipped
     * @return bool|string
     */
    public function render($asset, $target = '')
    {
        if (is_string($asset) && preg_match('/^(asset:\/\/)([a-zA-Z0-9\-]+)$/', $asset, $matches)) {
            $asset = $this->assetRepository->findByIdentifier($matches[2]);
        }

        if ($asset instanceof AssetInterface) {
            $uri = $this->resourceManager->getPublicPersistentResourceUri($asset->getResource());
            $this->tag->addAttribute('href', $uri);
            if ($target !== null) {
                $this->tag->addAttribute('target', $target);
            }
            $content = $this->renderChildren();
            if ($content === null && $asset !== null) {
                $content = $asset->getTitle();
            }
            $this->tag->setContent($content);
            $this->tag->forceClosingTag(true);
            return $this->tag->render();
        }
    }

}