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
use TYPO3\Flow\I18n\Locale;
use TYPO3\Flow\Security\Context;
use TYPO3\Flow\Utility\TypeHandling;
use TYPO3\Media\Domain\Model\Asset;
use TYPO3\Media\Domain\Model\AssetCollection;
use TYPO3\Neos\Controller\BackendUserTranslationTrait;
use TYPO3\Neos\Controller\CreateContentContextTrait;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use TYPO3\Neos\Domain\Service\UserService as DomainUserService;
use TYPO3\Neos\Service\UserService;
use TYPO3\TYPO3CR\Domain\Factory\NodeFactory;
use TYPO3\TYPO3CR\Domain\Model\Node;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
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
    use BackendUserTranslationTrait;

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
     * @Flow\Inject
     * @var DomainUserService
     */
    protected $domainUserService;

    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

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
        $this->view->assign('relatedNodes', $this->getRelatedNodes($asset));
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
            $this->addFlashMessage('media.deleteRelatedNodes', '', Message::SEVERITY_WARNING, [], 1412422767);
            $this->redirect('index');
        }

        // FIXME: Resources are not deleted, because we cannot be sure that the resource isn't used anywhere else.
        $this->assetRepository->remove($asset);
        $this->addFlashMessage('assetHasBeenDeleted', '', Message::SEVERITY_OK, [$asset->getLabel()], 1412375050);
        $this->redirect('index');
    }

    /**
     * Get Related Nodes for an asset
     *
     * @param Asset $asset
     * @return void
     */
    public function relatedNodesAction(Asset $asset)
    {
        $userWorkspace = $this->userService->getPersonalWorkspace();
        $relatedNodes = [];
        foreach ($this->getRelatedNodes($asset) as $relatedNodeData) {
            $accessible = $this->domainUserService->currentUserCanReadWorkspace($relatedNodeData->getWorkspace());
            if ($accessible) {
                $context = $this->createContextMatchingNodeData($relatedNodeData);
            } else {
                $context = $this->createContentContext($userWorkspace->getName());
            }
            $site = $context->getCurrentSite();
            $node = $this->nodeFactory->createFromNodeData($relatedNodeData, $context);
            $flowQuery = new FlowQuery([$node]);
            /** @var Node $documentNode */
            $documentNode = $flowQuery->closest('[instanceof TYPO3.Neos:Document]')->get(0);
            $documentNodeIdentifier = $documentNode instanceof NodeInterface ? $documentNode->getIdentifier() : null;
            $relatedNodes[$site->getNodeName()]['site'] = $site;
            $relatedNodes[$site->getNodeName()]['documentNodes'][$documentNodeIdentifier]['node'] = $documentNode;
            $relatedNodes[$site->getNodeName()]['documentNodes'][$documentNodeIdentifier]['nodes'][] = [
                'node' => $node,
                'nodeData' => $relatedNodeData,
                'contextDocumentNode' => $documentNode,
                'accessible' => $accessible
            ];
        }

        $this->view->assignMultiple([
            'asset' => $asset,
            'relatedNodes' => $relatedNodes,
            'contentDimensions' => $this->contentDimensionPresetSource->getAllPresets(),
            'userWorkspace' => $userWorkspace
        ]);
    }

    /**
     * @param \TYPO3\Media\Domain\Model\Asset $asset
     * @return array
     */
    protected function getRelatedNodes(\TYPO3\Media\Domain\Model\Asset $asset)
    {
        $relationMap = [];
        $relationMap[TypeHandling::getTypeForValue($asset)] = [$this->persistenceManager->getIdentifierByObject($asset)];

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
        return new Error($errorMessage, null, [get_class($this), $this->actionMethodName]);
    }

    /**
     * Add a translated flashMessage.
     *
     * @param string $messageBody The translation id for the message body.
     * @param string $messageTitle The translation id for the message title.
     * @param string $severity
     * @param array $messageArguments
     * @param integer $messageCode
     * @return void
     */
    public function addFlashMessage($messageBody, $messageTitle = '', $severity = Message::SEVERITY_OK, array $messageArguments = array(), $messageCode = null)
    {
        if (is_string($messageBody)) {
            $messageBody = $this->translator->translateById($messageBody, $messageArguments, null, null, 'Modules', 'TYPO3.Neos');
        }
        $messageTitle = $this->translator->translateById($messageTitle, $messageArguments, null, null, 'Modules', 'TYPO3.Neos');

        parent::addFlashMessage($messageBody, $messageTitle, $severity, $messageArguments, $messageCode);
    }
}
