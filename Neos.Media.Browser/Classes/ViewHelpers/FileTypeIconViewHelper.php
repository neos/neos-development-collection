<?php

namespace Neos\Media\Browser\ViewHelpers;

/*
* This file is part of the Neos.Media.Browser package.
*
* (c) Contributors of the Neos Project - www.neos.io
* (c) Robert Lemke, Flownative GmbH - www.flownative.com
*
* This package is Open Source Software. For the full copyright and license
* information, please view the LICENSE file which was distributed with this
* source code.
*/

use Neos\Media\Browser\Service\FileTypeIconService;
use Neos\Flow\Annotations\Inject;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\FluidAdaptor\Core\ViewHelper\AbstractTagBasedViewHelper;

/**
 * Renders an <img> HTML tag for a file type icon for a given filename.
 *
 * = Examples =
 *
 * <code title="Rendering a file type icon">
 * <mediaBrowser:fileTypeIcon filename="{assetProxy.filename}" height="16" />
 * </code>
 * <output>
 * (depending on the asset, no scaling applied)
 * <img src="_Resources/Static/.../Icons/16px/jpg.png" height="16" alt="file type alt text" />
 * </output>
 */
class FileTypeIconViewHelper extends AbstractTagBasedViewHelper
{
    /**
     * @Inject()
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
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
     * @param string $filename
     * @param integer|null $width
     * @param integer|null $height
     * @return string
     */
    public function render($filename, $width = null, $height = null)
    {
        $icon = FileTypeIconService::getIcon($filename, $width, $height);
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
