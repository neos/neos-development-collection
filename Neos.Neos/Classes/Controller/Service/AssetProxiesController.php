<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Controller\Service;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Mvc\View\ViewInterface;
use Neos\FluidAdaptor\View\TemplateView;
use Neos\Media\Domain\Model\AssetSource\AssetProxy\AssetProxyInterface;
use Neos\Media\Domain\Model\AssetSource\Neos\NeosAssetNotFoundException;
use Neos\Media\Domain\Model\Dto\AssetConstraints;
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
     * @var array<string,string>
     */
    protected $viewFormatToObjectNameMap = [
        'html' => TemplateView::class,
        'json' => AssetJsonView::class
    ];

    /**
     * A list of IANA media types which are supported by this controller
     *
     * @var array<int,string>
     * @see http://www.iana.org/assignments/media-types/index.html
     */
    protected $supportedMediaTypes = [
        'text/html',
        'application/json'
    ];

    /**
     * @param ViewInterface $view
     * @return void
     */
    protected function initializeView(ViewInterface $view)
    {
        $view->assign('asyncThumbnails', $this->asyncThumbnails);
    }

    /**
     * Shows a list of assets
     *
     * @param string $searchTerm An optional search term used for filtering the list of assets
     * @param int $limit The maximum number of results shown in total
     * @return void
     */
    public function indexAction(string $searchTerm = '', int $limit = 10): void
    {
        $serializedAssetConstraints = $this->request->hasArgument('constraints')
            ? $this->request->getArgument('constraints')
            : null;
        $assetConstraints = $serializedAssetConstraints
            ? AssetConstraints::fromArray(is_array($serializedAssetConstraints)
                ? $serializedAssetConstraints
                : [$serializedAssetConstraints])
            : AssetConstraints::create();
        $assetSources = $assetConstraints->applyToAssetSources($this->assetSourceService->getAssetSources());

        $assetProxyQueryResultsIterator = new \MultipleIterator(\MultipleIterator::MIT_NEED_ANY);
        foreach ($assetSources as $assetSource) {
            $assetRepository = $assetSource->getAssetProxyRepository();
            $assetRepository->filterByType($assetConstraints->applyToAssetTypeFilter());
            $assetProxyQueryResult = $assetRepository->findBySearchTerm($searchTerm);
            if ($assetProxyQueryResult->valid()) {
                $assetProxyQueryResultsIterator->attachIterator($assetProxyQueryResult);
            }
        }

        $totalAddedResults = 0;
        $assetProxiesByAssetSource = [];
        foreach ($assetProxyQueryResultsIterator as $assetProxies) {
            foreach ($assetProxies as $assetProxy) {
                if ($assetProxy instanceof AssetProxyInterface) {
                    $assetProxiesByAssetSource[$assetProxy->getAssetSource()->getIdentifier()][] = $assetProxy;
                    $totalAddedResults++;
                }
                if ($totalAddedResults === $limit) {
                    break 2;
                }
            }
        }
        $this->view->assign('assetProxiesByAssetSource', $assetProxiesByAssetSource);
    }

    /**
     * Shows a specific asset proxy
     *
     * @param string $assetSourceIdentifier
     * @param string $assetProxyIdentifier
     * @return void
     * @throws StopActionException
     */
    public function showAction(string $assetSourceIdentifier, string $assetProxyIdentifier): void
    {
        $assetSources = $this->assetSourceService->getAssetSources();
        if (!isset($assetSources[$assetSourceIdentifier])) {
            $this->throwStatus(404, 'Asset source not found');
        }

        $assetProxyRepository = $assetSources[$assetSourceIdentifier]->getAssetProxyRepository();
        try {
            $assetProxy = $assetProxyRepository->getAssetProxy($assetProxyIdentifier);
        } catch (NeosAssetNotFoundException $exception) {
            $this->throwStatus(404, 'Asset not found');
        }

        $this->view->assign('assetProxy', $assetProxy);
    }

    /**
     * @param string $assetSourceIdentifier
     * @param string $assetProxyIdentifier
     * @return void
     * @throws AssetSourceServiceException
     * @throws StopActionException
     */
    public function importAction(string $assetSourceIdentifier, string $assetProxyIdentifier): void
    {
        $assetSources = $this->assetSourceService->getAssetSources();
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
