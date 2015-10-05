<?php
namespace TYPO3\Neos\NodeTypes\TypoScriptObjects;

/*
 * This file is part of the TYPO3.Neos.NodeTypes package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Media\Domain\Model\Asset;
use TYPO3\Media\Domain\Model\Image;
use TYPO3\Media\Domain\Repository\AssetRepository;
use TYPO3\Media\Domain\Repository\ImageRepository;
use TYPO3\TypoScript\TypoScriptObjects\Helpers\FluidView;
use TYPO3\TypoScript\TypoScriptObjects\TemplateImplementation;

/**
 * In the site import command we load images and assets and Doctrine
 * serializes them in when we store the node properties as ObjectArray.
 *
 * This serialize removes the resource property without a clear reason
 * and there's no solution for this issue available yet. THIS TYPOSCRIPT
 * OBJECT IS A WORKAROUND! Usage is discouraged and on own risk of breakage
 * on further releases of Neos.
 #
 * @see NEOS-121
 * @deprecated DO NOT USE!
 */
class AssetListImplementation extends TemplateImplementation
{
    /**
     * @Flow\Inject
     * @var ImageRepository
     */
    protected $imageRepository;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @param FluidView $view
     * @return void
     */
    public function initializeView(FluidView $view)
    {
        $assets = $this->tsValue('assets');
        $processedAssets = array();

        /** @var Asset $asset */
        if (is_array($assets)) {
            foreach ($assets as $asset) {
                if ($asset->getResource() === null) {
                    if ($asset instanceof Image) {
                        $processedAssets[] = $this->imageRepository->findByIdentifier($asset->getIdentifier());
                    } elseif ($asset instanceof Asset) {
                        $processedAssets[] = $this->assetRepository->findByIdentifier($asset->getIdentifier());
                    }
                } else {
                    $processedAssets[] = $asset;
                }
            }
        }

        $view->assign('assets', $processedAssets);
    }
}
