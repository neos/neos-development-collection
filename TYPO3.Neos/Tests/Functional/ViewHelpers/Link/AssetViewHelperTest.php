<?php
namespace TYPO3\Neos\Tests\Functional\ViewHelpers\Link;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Persistence\Doctrine\PersistenceManager;
use TYPO3\Flow\Tests\FunctionalTestCase;
use TYPO3\Fluid\Core\ViewHelper\TemplateVariableContainer;
use TYPO3\Media\Domain\Model\Asset;
use TYPO3\Media\Domain\Repository\AssetRepository;
use TYPO3\Neos\ViewHelpers\Link\AssetViewHelper;

/**
 * Functional test for the AssetViewHelper
 */
class AssetViewHelperTest extends FunctionalTestCase
{

    protected static $testablePersistenceEnabled = true;

    /**
     * @var \TYPO3\Flow\Resource\ResourceManager
     */
    protected $resourceManager;

    /**
     * @var AssetViewHelper
     */
    protected $viewHelper;

    /**
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @var PersistenceManager
     */
    protected $persistenceManager;

    /**
     * @return void
     */
    public function setUp() {
        parent::setUp();
        $this->assetRepository = $this->objectManager->get('TYPO3\Media\Domain\Repository\AssetRepository');
        $this->persistenceManager = $this->objectManager->get('TYPO3\Flow\Persistence\Doctrine\PersistenceManager');

        $this->viewHelper = new AssetViewHelper();
        $this->resourceManager = $this->objectManager->get('TYPO3\Flow\Resource\ResourceManager');
        $templateVariableContainer = new TemplateVariableContainer(array());
        $this->inject($this->viewHelper, 'templateVariableContainer', $templateVariableContainer);

        $resource = $this->resourceManager->importResource(__DIR__ . '/../../Fixtures/Resources/417px-Mihaly_Csikszentmihalyi.jpg');
        $asset = new Asset($resource);
        $this->assetRepository->add($asset);
        $this->persistenceManager->persistAll();

        $templateVariableContainer->add('asset', $asset);

        $this->viewHelper->setRenderChildrenClosure(function () use ($templateVariableContainer) {
            $linkedNode = $templateVariableContainer->get('asset');
            return $linkedNode !== null ? $linkedNode->getLabel() : '';
        });

        $this->viewHelper->initialize();
    }

    /**
     * @test
     */
    public function assetViewHelperRendersLinkBasedOnIdentifier() {
        /** @var Asset $asset */
        $asset = $this->assetRepository->findAll()->getFirst();
        $resource = $asset->getResource();
        $identifier = $asset->getIdentifier();
        $asset = $this->assetRepository->findByIdentifier($identifier);

        $resourceUri = $this->resourceManager->getPublicPersistentResourceUri($resource);

        $this->assertSame(
            '<a href="' . $resourceUri . '" target="_blank">' . $asset->getLabel() . '</a>',
            $this->viewHelper->render('asset://' . $asset->getIdentifier())
        );
    }

    /**
     * @test
     */
    public function assetViewHelperRendersLinkBasedOnIdentifierAndCustomTargetIfSet() {
        /** @var Asset $asset */
        $asset = $this->assetRepository->findAll()->getFirst();
        $resource = $asset->getResource();
        $identifier = $asset->getIdentifier();
        $asset = $this->assetRepository->findByIdentifier($identifier);

        $resourceUri = $this->resourceManager->getPublicPersistentResourceUri($resource);

        $this->assertSame(
            '<a href="' . $resourceUri . '" target="_top">' . $asset->getLabel() . '</a>',
            $this->viewHelper->render('asset://' . $asset->getIdentifier(), '_top')
        );
    }

}