<?php
namespace TYPO3\Media\Tests\Functional\Fixtures\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Media".           *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Mvc\View\ViewInterface;
use TYPO3\Flow\Resource\ResourceManager;
use TYPO3\Fluid\View\TemplateView;
use TYPO3\Media\Domain\Model\Image;
use TYPO3\Media\Domain\Model\ImageVariant;
use TYPO3\Media\Domain\Repository\AssetRepository;

/**
 * An Image Controller for testing purposes
 *
 * @Flow\Scope("singleton")
 */
class ImageController extends ActionController
{
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
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        /** @var TemplateView $view */
        $view->setOption('templateRootPathPattern', '@packageResourcesPath/Private/');
        parent::initializeView($view);
    }

    /**
     * Imports a new image and persists it, including one variant
     *
     * @param string $importUri
     * @return string
     */
    public function importAction($importUri)
    {
        $imageResource = $this->resourceManager->importResource($importUri);

        $image = new Image($imageResource);
        $imageVariant = new ImageVariant($image);

        $this->assetRepository->add($image);
        $this->assetRepository->add($imageVariant);

        $this->response->setHeader('X-ImageVariantUuid', $this->persistenceManager->getIdentifierByObject($imageVariant));

        return 'ok';
    }

    /**
     * Upload a new image and return an image variant, a thumbnail and additional information like it would be
     * returned for the Neos backend.
     *
     * @param Image $image
     * @return string
     */
    public function uploadAction(Image $image)
    {
        $this->assetRepository->add($image);
        $imageVariant = new ImageVariant($image);
        $this->assetRepository->add($imageVariant);

        $thumbnail = $image->getThumbnail(100, 100);

        $this->response->setHeader('Content-Type', 'application/json');
        return json_encode(
            array(
                '__identity' => $this->persistenceManager->getIdentifierByObject($image),
                '__resourceUri' => $this->resourceManager->getPublicPersistentResourceUri($image->getResource()),
                'width' => $image->getWidth(),
                'height' => $image->getHeight(),
                'thumbnail' => array(
                    '__resourceUri' => $this->resourceManager->getPublicPersistentResourceUri($thumbnail->getResource()),
                    'width' => $thumbnail->getWidth(),
                    'height' => $thumbnail->getHeight(),
                ),
                'variants' => array(
                    array(
                        '__identity' => $this->persistenceManager->getIdentifierByObject($imageVariant),
                        '__resourceUri' => $this->resourceManager->getPublicPersistentResourceUri($imageVariant->getResource()),
                        'width' => $imageVariant->getWidth(),
                        'height' => $imageVariant->getHeight(),
                    )
                )
            )
        );
    }

    /**
     * Shows an image variant
     *
     * @param \TYPO3\Media\Domain\Model\ImageVariant $imageVariant
     * @return void
     */
    public function showImageVariantAction(ImageVariant $imageVariant)
    {
        $this->view->assign('imageVariant', $imageVariant);
    }
}
