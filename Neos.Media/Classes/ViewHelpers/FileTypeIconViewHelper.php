<?php
namespace Neos\Media\ViewHelpers;

/*
 * This file is part of the Neos.Media package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractTagBasedViewHelper;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Service\FileTypeIconService;

/**
 * Renders an <img> HTML tag for a file type icon for a given Neos.Media's asset instance
 *
 * = Examples =
 *
 * <code title="Rendering an asset file type icon">
 * <neos.media:fileTypeIcon asset="{assetObject}" height="16" />
 * </code>
 * <output>
 * (depending on the asset, no scaling applied)
 * <img src="_Resources/Static/Packages/Neos/Media/Icons/16px/jpg.png" height="16" alt="file type alt text" />
 * </output>
 *
 * <code title="Rendering a file type icon by given filename">
 * <neos.media:fileTypeIcon filename="{someFilename}" height="16" />
 * </code>
 * <output>
 * (depending on the asset, no scaling applied)
 * <img src="_Resources/Static/Packages/Neos/Media/Icons/16px/jpg.png" height="16" alt="file type alt text" />
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
     * Renders an <img> HTML tag for a file type icon for a given Neos.Media's asset instance
     *
     * @param AssetInterface|null $file The Asset object. DEPRECATED, use $asset instead!
     * @param AssetInterface|null $asset An Asset object to determine the file type icon for. Alternatively $filename can be specified.
     * @param string|null $filename  A filename to determine the file type icon for. Alternatively $asset can be specified.
     * @param integer|null $width
     * @param integer|null $height
     * @return string
     */
    public function render(AssetInterface $file = null, AssetInterface $asset = null, string $filename = null, $width = null, $height = null)
    {
        if ($asset === null) {
            $asset = $file;
        }
        if ($asset === null && $filename === null) {
            throw new \InvalidArgumentException('You must either specify "asset" or "filename" for the ' . __CLASS__ . '.', 1524039575);
        }

        if ($asset instanceof AssetInterface) {
            $filename = $asset->getResource()->getFilename();
        }

        $icon = FileTypeIconService::getIcon($filename);
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
