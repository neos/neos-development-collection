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

use Neos\Error\Messages\Message;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\Media\Browser\Domain\Session\BrowserState;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Model\Dto\AssetConstraints;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Media\Domain\Repository\TagRepository;
use Neos\Neos\Domain\Repository\SiteRepository;

/**
 * Controller for tag handling
 *
 * @Flow\Scope("singleton")
 */
class AssetCollectionController extends ActionController
{
    use AddFlashMessageTrait;

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

    protected function initializeView(ViewInterface $view)
    {
        $view->assign('constraints', $this->request->hasArgument('constraints') ? AssetConstraints::fromArray($this->request->getArgument('constraints')) : AssetConstraints::create());
        parent::initializeView($view);
    }

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
        $this->redirectToAssetIndex();
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
        $this->redirectToAssetIndex();
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
        $this->redirectToAssetIndex();
    }

    /**
     * Overridden redirect method that points to the "index" action of the "Asset" controller and adds constraints arguments from the current request
     *
     * @param array $arguments
     * @throws StopActionException
     */
    private function redirectToAssetIndex(array $arguments = []): void
    {
        if (!isset($arguments['constraints']) && $this->request->hasArgument('constraints')) {
            $arguments['constraints'] = $this->request->getArgument('constraints');
        }
        $this->redirect('index', 'Asset', 'Neos.Media.Browser', $arguments);
    }
}
