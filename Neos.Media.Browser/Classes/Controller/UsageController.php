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

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Service\AssetService;

/**
 * Controller for asset usage handling
 *
 * @Flow\Scope("singleton")
 */
class UsageController extends ActionController
{
    /**
     * @Flow\Inject
     * @var AssetService
     */
    protected $assetService;

    /**
     * Display usages for an asset
     *
     * @param AssetInterface $asset
     * @return void
     */
    public function indexAction(AssetInterface $asset)
    {
        $usageStrategiesAndReferences = [];
        foreach ($this->assetService->getUsageStrategies() as $usageStrategy) {
            $usageReferences = $usageStrategy->getUsageReferences($asset);
            if ($usageReferences === []) {
                continue;
            }
            $usageStrategiesAndReferences[] = [
                'strategy' => $usageStrategy,
                'usageReferences' => $usageReferences,
            ];
        }
        $this->view->assignMultiple([
            'asset' => $asset,
            'usageStrategiesAndReferences' => $usageStrategiesAndReferences
        ]);
    }
}
