<?php
namespace TYPO3\Neos\Controller\Module\Management;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Error\Message;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;

/**
 * Controller for asset handling
 *
 * @Flow\Scope("singleton")
 */
class AssetController extends \TYPO3\Media\Controller\AssetController
{
    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     *
     */
    public function initializeObject()
    {
        $this->settings = $this->configurationManager->getConfiguration('Settings', 'TYPO3.Media');
    }

    /**
     * Delete an asset
     *
     * @param \TYPO3\Media\Domain\Model\Asset $asset
     * @return void
     */
    public function deleteAction(\TYPO3\Media\Domain\Model\Asset $asset)
    {
        $identifier = $this->persistenceManager->getIdentifierByObject($asset);
        $relatedNodes = $this->nodeDataRepository->findByRelationWithGivenPersistenceIdentifierAndObjectTypeMap($identifier, array(
            'TYPO3\Media\Domain\Model\Asset' => '',
            'TYPO3\Media\Domain\Model\ImageVariant' => 'originalImage'
        ));
        if (count($relatedNodes) > 0) {
            $this->addFlashMessage('Asset could not be deleted, because there are still Nodes using it.', '', Message::SEVERITY_WARNING);
            $this->redirect('index');
        }

        // FIXME: Resources are not deleted, because we cannot be sure that the resource isn't used anywhere else.
        $this->assetRepository->remove($asset);
        $this->addFlashMessage('Asset has been deleted.');
        $this->redirect('index');
    }
}
