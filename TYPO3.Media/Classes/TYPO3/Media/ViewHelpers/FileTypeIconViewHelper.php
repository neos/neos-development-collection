<?php
namespace TYPO3\Media\ViewHelpers;

/*
 * This file is part of the TYPO3.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper;
use TYPO3\Media\Domain\Model\AssetInterface;
use TYPO3\Media\Domain\Service\FileTypeIconService;

/**
 * Renders an <img> HTML tag for a filetype icon for a given TYPO3.Media's asset instance
 *
 * = Examples =
 *
 * <code title="Rendering an asset filetype icon">
 * <typo3.media:fileTypeIcon asset="{assetObject}" alt="a filetype icon" height="16" />
 * </code>
 * <output>
 * (depending on the asset, no scaling applied)
 * <img src="_Resources/Static/Packages/TYPO3/Media/Icons/16px/jpg.png" height="16" alt="a filetype icon" />
 * </output>
 *
 */
class FileTypeIconViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * name of the tag to be created by this view helper
     *
     * @var string
     */
    protected $tagName = 'img';

    /**
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerUniversalTagAttributes();
    }

    /**
     * Renders an <img> HTML tag for a filetype icon for a given TYPO3.Media's asset instance
     *
     * @param AssetInterface $file
     * @param integer|null $width
     * @param integer|null $height
     * @return string
     */
    public function render(AssetInterface $file, $width = null, $height = null)
    {
        $icon = FileTypeIconService::getIcon($file, $width, $height);
        $this->tag->addAttribute('src', $this->resourceManager->getPublicPackageResourceUriByPath($icon['src']));
        $this->tag->addAttribute('alt', $icon['alt']);

        if ($width !== null) {
            $this->tag->addAttribute('width', $width);
        }

        if ($height !== null) {
            $this->tag->addAttribute('height', $height);
        }

        return $this->tag->render();
    }
}
