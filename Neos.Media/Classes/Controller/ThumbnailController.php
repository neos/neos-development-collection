<?php
namespace Neos\Media\Controller;

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
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Media\Domain\Model\Thumbnail;
use Neos\Media\Domain\Service\ThumbnailService;

/**
 * Controller for asynchronous thumbnail handling
 *
 * @Flow\Scope("singleton")
 */
class ThumbnailController extends ActionController
{
    /**
     * @Flow\Inject
     * @var ThumbnailService
     */
    protected $thumbnailService;

    /**
     * Generate thumbnail and redirect to resource URI
     *
     * @param Thumbnail $thumbnail
     * @return void
     */
    public function thumbnailAction(Thumbnail $thumbnail)
    {
        if ($thumbnail->getResource() === null && $thumbnail->getStaticResource() === null) {
            $this->thumbnailService->refreshThumbnail($thumbnail);
        }
        $this->redirectToUri($this->thumbnailService->getUriForThumbnail($thumbnail), 0, 302);
    }
}
