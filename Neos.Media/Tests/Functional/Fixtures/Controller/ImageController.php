<?php
namespace Neos\Media\Tests\Functional\Fixtures\Controller;

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
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Flow\Persistence\Exception\IllegalObjectTypeException;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\FluidAdaptor\View\TemplateView;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Model\ImageVariant;
use Neos\Media\Domain\Repository\AssetRepository;

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
     * @throws \Neos\Flow\Mvc\Exception
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
     * @throws IllegalObjectTypeException
     * @throws \Neos\Flow\ResourceManagement\Exception
     * @throws \Exception
     */
    public function importAction($importUri)
    {
        $imageResource = $this->resourceManager->importResource($importUri);

        $image = new Image($imageResource);
        $imageVariant = new ImageVariant($image);

        $this->assetRepository->add($image);
        $this->assetRepository->add($imageVariant);

        $this->response->setHttpHeader('X-ImageVariantUuid', $this->persistenceManager->getIdentifierByObject($imageVariant));
        return 'ok';
    }

    /**
     * Upload a new image and return an image variant, a thumbnail and additional information like it would be
     * returned for the Neos backend.
     *
     * @param Image $image
     * @return string
     * @throws IllegalObjectTypeException
     */
    public function uploadAction(Image $image)
    {
        $this->assetRepository->add($image);
        $imageVariant = new ImageVariant($image);
        $this->assetRepository->add($imageVariant);

        $thumbnail = $image->getThumbnail(100, 100);

        $this->response->setContentType('application/json');
        return json_encode(
            [
                '__identity' => $this->persistenceManager->getIdentifierByObject($image),
                '__resourceUri' => $this->resourceManager->getPublicPersistentResourceUri($image->getResource()),
                'width' => $image->getWidth(),
                'height' => $image->getHeight(),
                'thumbnail' => [
                    '__resourceUri' => $this->resourceManager->getPublicPersistentResourceUri($thumbnail->getResource()),
                    'width' => $thumbnail->getWidth(),
                    'height' => $thumbnail->getHeight(),
                ],
                'variants' => [
                    [
                        '__identity' => $this->persistenceManager->getIdentifierByObject($imageVariant),
                        '__resourceUri' => $this->resourceManager->getPublicPersistentResourceUri($imageVariant->getResource()),
                        'width' => $imageVariant->getWidth(),
                        'height' => $imageVariant->getHeight(),
                    ]
                ]
            ]
        );
    }

    /**
     * Shows an image variant
     *
     * @param \Neos\Media\Domain\Model\ImageVariant $imageVariant
     * @return void
     */
    public function showImageVariantAction(ImageVariant $imageVariant)
    {
        $this->view->assign('imageVariant', $imageVariant);
    }
}
