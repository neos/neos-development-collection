<?php
namespace TYPO3\Media\Controller;

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
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Media\Domain\Model\Thumbnail;
use TYPO3\Media\Domain\Repository\ThumbnailRepository;
use TYPO3\Media\Domain\Service\ThumbnailService;

/**
 * Controller for asynchronous thumbnail handling
 *
 * @Flow\Scope("singleton")
 */
class ThumbnailController extends \TYPO3\Flow\Mvc\Controller\ActionController
{
    /**
     * @Flow\Inject
     * @var ThumbnailService
     */
    protected $thumbnailService;

    /**
     * @Flow\Inject
     * @var ResourceManager
     */
    protected $resourceManager;

    /**
     * Generate thumbnail and redirect to resource URI
     *
     * @param Thumbnail $thumbnail
     * @return void
     */
    public function thumbnailAction(Thumbnail $thumbnail)
    {
        if ($thumbnail->getResource() === null) {
            $this->thumbnailService->refreshThumbnail($thumbnail);
        }
        $this->redirectToUri($this->resourceManager->getPublicPersistentResourceUri($thumbnail->getResource()), 0, 302);
    }
}
