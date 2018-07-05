<?php

namespace Neos\Media\Browser\Controller;

/*
 * This file is part of the Neos.Media.Browser package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\Error\Messages\Message;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Media\Browser\Domain\Session\BrowserState;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Media\Domain\Repository\TagRepository;
use Neos\Media\Domain\Service\AssetService;
use Neos\Neos\Controller\CreateContentContextTrait;
use Neos\Neos\Domain\Model\Dto\AssetUsageInNodeProperties;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use Neos\Neos\Service\UserService;
use Neos\Neos\Domain\Service\UserService as DomainUserService;

/**
 * Controller for tag handling
 *
 * @Flow\Scope("singleton")
 */
class AssetCollectionController extends ActionController
{
    /**
     * @Flow\Inject
     * @var AssetCollectionRepository
     */
    protected $assetCollectionRepository;

    /**
     * @Flow\Inject
     * @var BrowserState
     */
    protected $browserState;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var TagRepository
     */
    protected $tagRepository;

    /**
     * @param string $title
     * @return void
     * @Flow\Validate(argumentName="title", type="NotEmpty")
     * @Flow\Validate(argumentName="title", type="Label")
     */
    public function createAction($title)
    {
        $this->assetCollectionRepository->add(new AssetCollection($title));
        $this->addFlashMessage('collectionHasBeenCreated', '', Message::SEVERITY_OK, [htmlspecialchars($title)]);
        $this->redirect('index', 'Asset', 'Neos.Media.Browser');
    }

    /**
     * @param AssetCollection $assetCollection
     * @return void
     */
    public function editAction(AssetCollection $assetCollection)
    {
        $this->view->assignMultiple([
            'assetCollection' => $assetCollection,
            'tags' => $this->tagRepository->findAll()
        ]);
    }

    /**
     * @param AssetCollection $assetCollection
     * @return void
     */
    public function updateAction(AssetCollection $assetCollection)
    {
        $this->assetCollectionRepository->update($assetCollection);
        $this->addFlashMessage('collectionHasBeenUpdated', '', Message::SEVERITY_OK, [htmlspecialchars($assetCollection->getTitle())]);
        $this->redirect('index', 'Asset', 'Neos.Media.Browser');
    }

    /**
     * @param AssetCollection $assetCollection
     * @return void
     */
    public function deleteAction(AssetCollection $assetCollection)
    {
        foreach ($this->siteRepository->findByAssetCollection($assetCollection) as $site) {
            $site->setAssetCollection(null);
            $this->siteRepository->update($site);
        }

        if ($this->browserState->get('activeAssetCollection') === $assetCollection) {
            $this->browserState->set('activeAssetCollection', null);
        }
        $this->assetCollectionRepository->remove($assetCollection);
        $this->addFlashMessage('collectionHasBeenDeleted', '', Message::SEVERITY_OK, [htmlspecialchars($assetCollection->getTitle())]);
        $this->redirect('index', 'Asset', 'Neos.Media.Browser');
    }
}
