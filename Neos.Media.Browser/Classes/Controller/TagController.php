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
use Neos\Media\Browser\Domain\Session\BrowserState;
use Neos\Media\Domain\Model\Tag;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Repository\TagRepository;

/**
 * Controller for tag handling
 *
 * @Flow\Scope("singleton")
 */
class TagController extends ActionController
{
    use AddFlashMessageTrait;

    /**
     * @Flow\Inject
     * @var AssetCollectionRepository
     */
    protected $assetCollectionRepository;

    /**
     * @Flow\Inject
     * @var TagRepository
     */
    protected $tagRepository;

    /**
     * @Flow\Inject
     * @var BrowserState
     */
    protected $browserState;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @param string $label
     * @return void
     * @Flow\Validate(argumentName="label", type="NotEmpty")
     * @Flow\Validate(argumentName="label", type="Label")
     */
    public function createAction($label)
    {
        $existingTag = $this->tagRepository->findOneByLabel($label);
        if ($existingTag !== null) {
            if (($assetCollection = $this->browserState->get('activeAssetCollection')) !== null && $assetCollection->addTag($existingTag)) {
                $this->assetCollectionRepository->update($assetCollection);
                $this->addFlashMessage('tagAlreadyExistsAndAddedToCollection', '', Message::SEVERITY_OK, [htmlspecialchars($label)]);
            }
        } else {
            $tag = new Tag($label);
            $this->tagRepository->add($tag);
            if (($assetCollection = $this->browserState->get('activeAssetCollection')) !== null && $assetCollection->addTag($tag)) {
                $this->assetCollectionRepository->update($assetCollection);
            }
            $this->addFlashMessage('tagHasBeenCreated', '', Message::SEVERITY_OK, [htmlspecialchars($label)]);
        }
        $this->redirect('index', 'Asset', 'Neos.Media.Browser');
    }

    /**
     * @param Tag $tag
     * @return void
     */
    public function editAction(Tag $tag)
    {
        $this->view->assignMultiple([
            'tag' => $tag,
            'assetCollections' => $this->assetCollectionRepository->findAll()
        ]);
    }

    /**
     * @param Tag $tag
     * @return void
     */
    public function updateAction(Tag $tag)
    {
        $this->tagRepository->update($tag);
        $this->addFlashMessage('tagHasBeenUpdated', '', Message::SEVERITY_OK, [htmlspecialchars($tag->getLabel())]);
        $this->redirect('index', 'Asset', 'Neos.Media.Browser');
    }

    /**
     * @param Tag $tag
     * @return void
     */
    public function deleteAction(Tag $tag)
    {
        $taggedAssets = $this->assetRepository->findByTag($tag);
        foreach ($taggedAssets as $asset) {
            $asset->removeTag($tag);
            $this->assetRepository->update($asset);
        }
        $this->tagRepository->remove($tag);
        $this->addFlashMessage('tagHasBeenDeleted', '', Message::SEVERITY_OK, [htmlspecialchars($tag->getLabel())]);
        $this->redirect('index', 'Asset', 'Neos.Media.Browser');
    }
}
