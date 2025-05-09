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

use Neos\ContentRepository\Core\Dimension\ContentDimensionId;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
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
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $interDimensionalVariationGraph = $this->contentRepositoryRegistry->get($contentRepositoryId)
            ->getVariationGraph();

        if ($this->view instanceof JsonView) {
            $this->view->assign(
                'value',
                $interDimensionalVariationGraph->getDimensionSpacePoints()
            );
        } else {
            $this->view->assign(
                'contentDimensionsPresets',
                $interDimensionalVariationGraph->getDimensionSpacePoints()
            );
        }
    }

    /**
     * Returns only presets of the dimension specified by $dimensionName. But even though only one dimension is returned,
     * the output follows the structure as described in ContentDimensionPresetSourceInterface::getAllPresets().
     *
     * It is possible to pass a selection of presets as a filter. In that case, $chosenDimensionPresets must be an array
     * of one or more dimension names (key) and preset names (value). The returned list will then only contain dimension
     * presets which are allowed in combination with the given presets.
     *
     * Example: Given that $chosenDimensionPresets = array('country' => 'US') and that a second dimension "language"
     * exists and $dimensionName is "language. This method will now display a list of dimension presets for the dimension
     * "language" which are allowed in combination with the country "US".
     *
     * @param string $dimensionName Name of the dimension to return presets for
     * @param array $chosenDimensionPresets An optional array of dimension names and a single preset per dimension
     * @phpstan-param array<string,string> $chosenDimensionPresets
     * @return void
     */
    public function showAction($dimensionName, $chosenDimensionPresets = []): void
    {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $contentDimensionSource = $contentRepository->getContentDimensionSource();

        $contentDimensionId = new ContentDimensionId($dimensionName);
        $contentDimension = $contentDimensionSource->getDimension($contentDimensionId);
        if (is_null($contentDimension)) {
            $this->throwStatus(404, sprintf('The dimension %s does not exist.', $dimensionName));
        }

        $interDimensionalVariationGraph = $contentRepository->getVariationGraph();
        $allowedSubSpace = $interDimensionalVariationGraph->getDimensionSpacePoints();

        $selectedDimensionSpacepoint = DimensionSpacePoint::fromArray($chosenDimensionPresets);
        $allowedDimensionValues = [];
        foreach ($contentDimension->values as $contentDimensionValue) {
            $probeDimensionSpacepoint = $selectedDimensionSpacepoint->vary(
                $contentDimensionId,
                $contentDimensionValue->value
            );

            if ($allowedSubSpace->contains($probeDimensionSpacepoint)) {
                $allowedDimensionValues[] = $contentDimensionValue;
            }
        }

        // Build Legacy Response Shape
        $contentDimensionsAndPresets = [
            $contentDimensionId->value => [
                'label' => $contentDimension->getConfigurationValue('label'),
                'icon' => $contentDimension->getConfigurationValue('icon'),
                'presets' => []
            ]
        ];
        foreach ($allowedDimensionValues as $allowedDimensionValue) {
            $contentDimensionsAndPresets[$contentDimensionId->value]['presets'][$allowedDimensionValue->value] = [
                'label' => $allowedDimensionValue->getConfigurationValue('label')
            ];
        }

        if ($this->view instanceof JsonView) {
            $this->view->assign('value', $contentDimensionsAndPresets);
        } else {
            $this->view->assign('dimensionName', $dimensionName);
            $this->view->assign('contentDimensionsPresets', $contentDimensionsAndPresets);
        }
    }
}
