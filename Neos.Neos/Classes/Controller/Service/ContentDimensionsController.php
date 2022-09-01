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

use Neos\ContentRepository\Core\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Mvc\View\JsonView;
use Neos\FluidAdaptor\View\TemplateView;
use Neos\Neos\Controller\BackendUserTranslationTrait;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;

/**
 * REST service controller for managing content dimensions
 */
class ContentDimensionsController extends ActionController
{
    use BackendUserTranslationTrait;

    /**
     * @var array<string,string>
     */
    protected $viewFormatToObjectNameMap = [
        'html' => TemplateView::class,
        'json' => JsonView::class
    ];

    /**
     * @var array<int,string>
     */
    protected $supportedMediaTypes = [
        'text/html',
        'application/json'
    ];

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * Returns the full content dimensions presets as JSON object; see
     * ContentDimensionPresetSourceInterface::getAllPresets() for a format description.
     *
     * @return void
     */
    public function indexAction()
    {
        $contentRepositoryIdentifier = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $controllerInternals = $this->contentRepositoryRegistry->getService(
            $contentRepositoryIdentifier,
            new ContentDimensionsControllerInternalsFactory()
        );

        if ($this->view instanceof JsonView) {
            $this->view->assign(
                'value',
                $controllerInternals->contentDimensionZookeeper->getAllowedDimensionSubspace()
            );
        } else {
            $this->view->assign(
                'contentDimensionsPresets',
                $controllerInternals->contentDimensionZookeeper->getAllowedDimensionSubspace()
            );
        }
    }
}
