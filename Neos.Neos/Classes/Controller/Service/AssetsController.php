<?php
namespace Neos\Neos\Controller\Service;

/*
 * This file is part of the Neos.Neos package.
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
use Neos\FluidAdaptor\View\TemplateView;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Repository\TagRepository;
use Neos\Neos\Controller\BackendUserTranslationTrait;
use Neos\Neos\View\Service\AssetJsonView;

/**
 * Rudimentary REST service for assets
 *
 * @Flow\Scope("singleton")
 */
class AssetsController extends ActionController
{
    use BackendUserTranslationTrait;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var TagRepository
     */
    protected $tagRepository;

    /**
     * @Flow\InjectConfiguration(package="Neos.Media", path="asyncThumbnails")
     * @var boolean
     */
    protected $asyncThumbnails;

    /**
     * @var array
     */
    protected $viewFormatToObjectNameMap = array(
        'html' => TemplateView::class,
        'json' => AssetJsonView::class
    );

    /**
     * A list of IANA media types which are supported by this controller
     *
     * @var array
     * @see http://www.iana.org/assignments/media-types/index.html
     */
    protected $supportedMediaTypes = array(
        'text/html',
        'application/json'
    );

    /**
     * @param ViewInterface $view
     * @return void
     */
    public function initializeView(ViewInterface $view)
    {
        $view->assign('asyncThumbnails', $this->asyncThumbnails);
    }

    /**
     * Shows a list of assets
     *
     * @param string $searchTerm An optional search term used for filtering the list of assets
     * @return string
     */
    public function indexAction($searchTerm = '')
    {
        $assets = $this->assetRepository->findBySearchTermOrTags(
            $searchTerm,
            $this->tagRepository->findBySearchTerm($searchTerm)->toArray()
        );

        $this->view->assign('assets', $assets);
    }

    /**
     * Shows a specific asset
     *
     * @param string $identifier Specifies the asset to look up
     * @return string
     */
    public function showAction($identifier)
    {
        $asset = $this->assetRepository->findByIdentifier($identifier);

        if ($asset === null) {
            $this->throwStatus(404);
        }

        $this->view->assign('asset', $asset);
    }
}
