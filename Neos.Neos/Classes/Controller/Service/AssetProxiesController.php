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
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Mvc\Exception\UnsupportedRequestTypeException;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\FluidAdaptor\View\TemplateView;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Service\AssetSourceService;
use Neos\Media\Domain\Repository\TagRepository;
use Neos\Media\Exception\AssetSourceServiceException;
use Neos\Neos\Controller\BackendUserTranslationTrait;
use Neos\Neos\View\Service\AssetJsonView;

/**
 * Rudimentary REST service for assets
 *
 * @Flow\Scope("singleton")
 */
class AssetProxiesController extends ActionController
{
    use BackendUserTranslationTrait;

    /**
     * @Flow\Inject
     * @var AssetRepository
     */
    protected $assetRepository;

    /**
     * @Flow\Inject
     * @var AssetSourceService
     */
    protected $assetSourceService;

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
     * @param string $assetSourceIdentifier If specified, results are only from the given asset source
     * @return string
     */
    public function indexAction(string $searchTerm = '', string $assetSourceIdentifier = '')
    {
        $assetSources = $this->assetSourceService->getAssetsSources();
        $assetProxies = [];
        foreach ($assetSources as $assetSource) {
            if ($assetSourceIdentifier !== '' && $assetSource->getIdentifier() !== $assetSourceIdentifier) {
                continue;
            }
           try {
                $assetProxyRepository = $assetSource->getAssetProxyRepository();
                $assetProxiesInThisSource = $assetProxyRepository->findBySearchTerm($searchTerm);

                $assetProxies = array_merge($assetProxies, $assetProxiesInThisSource->toArray());
            } catch (\Exception $exception) {
            }
        }
        $this->view->assign('assetProxies', $assetProxies);
    }

    /**
     * Shows a specific asset proxy
     *
     * @param string $assetSourceIdentifier
     * @param string $assetProxyIdentifier
     * @return string
     * @throws StopActionException
     * @throws UnsupportedRequestTypeException
     */
    public function showAction(string $assetSourceIdentifier, string $assetProxyIdentifier)
    {
        $assetSources = $this->assetSourceService->getAssetsSources();
        if (!isset($assetSources[$assetSourceIdentifier])) {
            $this->throwStatus(404, 'Asset source not found');
        }

        $assetProxyRepository = $assetSources[$assetSourceIdentifier]->getAssetProxyRepository();
        $assetProxy = $assetProxyRepository->getAssetProxy($assetProxyIdentifier);
        if (!$assetProxy) {
            $this->throwStatus(404, 'Asset proxy not found');
        }

        $this->view->assign('assetProxy', $assetProxy);
    }

    /**
     * @param string $assetSourceIdentifier
     * @param string $assetProxyIdentifier
     * @throws AssetSourceServiceException
     * @throws StopActionException
     * @throws UnsupportedRequestTypeException
     */
    public function importAction(string $assetSourceIdentifier, string $assetProxyIdentifier)
    {
        $assetSources = $this->assetSourceService->getAssetsSources();
        if (!isset($assetSources[$assetSourceIdentifier])) {
            $this->throwStatus(404, 'Asset source not found');
        }

        $importedAsset = $this->assetSourceService->importAsset($assetSourceIdentifier, $assetProxyIdentifier);

        $assetProxy = new \stdClass();
        $assetProxy->identifier = $assetProxyIdentifier;
        $assetProxy->assetSource = $assetSources[$assetSourceIdentifier];
        $assetProxy->localAssetIdentifier = $importedAsset->getLocalAssetIdentifier();
        $this->view->assign('assetProxy', $assetProxy);
    }
}
