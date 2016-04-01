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

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Configuration\ConfigurationManager;
use TYPO3\Flow\Error\Error;
use TYPO3\Flow\Error\Message;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Utility\TypeHandling;
use TYPO3\Fluid\Core\Parser\SyntaxTree\NodeInterface;
use TYPO3\Media\Domain\Model\Asset;
use TYPO3\Media\Domain\Model\AssetCollection;
use TYPO3\Neos\Controller\CreateContentContextTrait;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Service\UserService;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Model\NodeData;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;

/**
 * Controller for asset handling
 *
 * @Flow\Scope("singleton")
 */
class AssetController extends \TYPO3\Media\Controller\AssetController
{
    use CreateContentContextTrait;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

    /**
     * @Flow\Inject
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * @Flow\Inject
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var DomainRepository
     */
    protected $domainRepository;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @return void
     */
    public function initializeObject()
    {
        $this->settings = $this->configurationManager->getConfiguration('Settings', 'TYPO3.Media');
        $domain = $this->domainRepository->findOneByActiveRequest();
        // Set active asset collection to the current site's asset collection, if it has one, on the first view if a matching domain is found
        if ($domain !== null && !$this->browserState->get('activeAssetCollection') && $this->browserState->get('automaticAssetCollectionSelection') !== true && $domain->getSite()->getAssetCollection() !== null) {
            $this->browserState->set('activeAssetCollection', $domain->getSite()->getAssetCollection());
            $this->browserState->set('automaticAssetCollectionSelection', true);
        }
    }

    /**
     * Edit an asset
     *
     * @param Asset $asset
     * @return void
     */
    public function editAction(Asset $asset)
    {
        parent::editAction($asset);

        $relatedDocumentNodes = [];
        foreach ($this->getRelatedNodes($asset) as $relatedNodeData) {
            $node = $this->nodeFactory->createFromNodeData($relatedNodeData, $this->createContentContext($this->userService->getPersonalWorkspaceName()));
            $flowQuery = new FlowQuery(array($node));
            /** @var Node $documentNode */
            $documentNode = $flowQuery->closest('[instanceof TYPO3.Neos:Document]')->get(0);
            $relatedDocumentNodes[$documentNode->getIdentifier()] = $documentNode;
        }

        $this->view->assign('relatedDocumentNodes', $relatedDocumentNodes);
    }

    /**
     * Delete an asset
     *
     * @param \TYPO3\Media\Domain\Model\Asset $asset
     * @return void
     */
    public function deleteAction(\TYPO3\Media\Domain\Model\Asset $asset)
    {
        $relatedNodes = $this->getRelatedNodes($asset);
        if (count($relatedNodes) > 0) {
            $this->addFlashMessage('Asset could not be deleted, because there are still Nodes using it.', '', Message::SEVERITY_WARNING, array(), 1412422767);
            $this->redirect('index');
        }

        // FIXME: Resources are not deleted, because we cannot be sure that the resource isn't used anywhere else.
        $this->assetRepository->remove($asset);
        $this->addFlashMessage(sprintf('Asset "%s" has been deleted.', $asset->getLabel()), null, null, array(), 1412375050);
        $this->redirect('index');
    }

    /**
     * Get Related Document Nodes from an asset
     *
     * @param Asset $asset
     * @return void
     */
    public function relatedDocumentNodesAction(Asset $asset)
    {
        $relatedDocumentNodes = [];
        foreach ($this->getRelatedNodes($asset) as $relatedNodeData) {
            $node = $this->nodeFactory->createFromNodeData($relatedNodeData, $this->createContentContext($this->userService->getPersonalWorkspaceName()));
            $flowQuery = new FlowQuery(array($node));
            /** @var Node $documentNode */
            $documentNode = $flowQuery->closest('[instanceof TYPO3.Neos:Document]')->get(0);
            $relatedDocumentNodes[$documentNode->getIdentifier()]['documentNode'] = $documentNode;
            $relatedDocumentNodes[$documentNode->getIdentifier()]['node'][] = $node;
        }

        $this->view->assignMultiple(array(
            'asset' => $asset,
            'relatedDocumentNodes' => $relatedDocumentNodes
        ));
    }

    /**
     * @param \TYPO3\Media\Domain\Model\Asset $asset
     * @return array
     */
    protected function getRelatedNodes(\TYPO3\Media\Domain\Model\Asset $asset)
    {
        $relationMap = [];
        $relationMap[TypeHandling::getTypeForValue($asset)] = array($this->persistenceManager->getIdentifierByObject($asset));

        if ($asset instanceof \TYPO3\Media\Domain\Model\Image) {
            foreach ($asset->getVariants() as $variant) {
                $type = TypeHandling::getTypeForValue($variant);
                if (!isset($relationMap[$type])) {
                    $relationMap[$type] = [];
                }
                $relationMap[$type][] = $this->persistenceManager->getIdentifierByObject($variant);
            }
        }

        return $this->nodeDataRepository->findNodesByRelatedEntities($relationMap);
    }

    /**
     * @param AssetCollection $assetCollection
     * @return void
     */
    public function deleteAssetCollectionAction(AssetCollection $assetCollection)
    {
        foreach ($this->siteRepository->findByAssetCollection($assetCollection) as $site) {
            $site->setAssetCollection(null);
            $this->siteRepository->update($site);
        }
        parent::deleteAssetCollectionAction($assetCollection);
    }

    /**
     * This custom errorAction adds FlashMessages for validation results to give more information in the
     *
     * @return string
     */
    protected function errorAction()
    {
        foreach ($this->arguments->getValidationResults()->getFlattenedErrors() as $propertyPath => $errors) {
            foreach ($errors as $error) {
                $this->flashMessageContainer->addMessage($error);
            }
        }

        return parent::errorAction();
    }

    /**
     * Individual error FlashMessage that hides which action fails in production.
     *
     * @return \TYPO3\Flow\Error\Message The flash message or FALSE if no flash message should be set
     */
    protected function getErrorFlashMessage()
    {
        if ($this->arguments->getValidationResults()->hasErrors()) {
            return false;
        }
        $errorMessage = 'An error occurred';
        if ($this->objectManager->getContext()->isDevelopment()) {
            $errorMessage .= ' while trying to call %1$s->%2$s()';
        }
        return new Error($errorMessage, null, array(get_class($this), $this->actionMethodName));
    }
}
