<?php
namespace Neos\EventSourcedNeosAdjustments\Ui\Fusion;

/*
 * This file is part of the Neos.Neos.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Exception;
use Neos\Fusion\FusionObjects\AbstractFusionObject;
use Neos\Neos\Ui\Domain\Service\ConfigurationRenderingService;
use Neos\Utility\Arrays;

class RenderConfigurationImplementation extends AbstractFusionObject
{

    /**
     * @Flow\Inject
     * @var ConfigurationRenderingService
     */
    protected $configurationRenderingService;

    /**
     * @Flow\InjectConfiguration(package="Neos.Neos.Ui")
     * @var array
     */
    protected $settings;

    /**
     * @Flow\InjectConfiguration(package="Neos.EventSourcedNeosAdjustments")
     * @var array
     */
    protected $settingsEventSourced;

    /**
     * @return array
     */
    protected function getContext(): array
    {
        return $this->fusionValue('context');
    }

    /**
     * @return string
     */
    protected function getPath(): string
    {
        return $this->fusionValue('path');
    }

    /**
     * Appends an item to the given collection
     *
     * @return array
     * @throws Exception
     */
    public function evaluate()
    {
        $context = $this->getContext();
        $pathToRender = $this->getPath();
        $context['controllerContext'] = $this->getruntime()->getControllerContext();

        $settings = Arrays::arrayMergeRecursiveOverrule($this->settings, $this->settingsEventSourced);
        if (!isset($settings[$pathToRender])) {
            throw new Exception('The path "Neos.Neos.Ui.' . $pathToRender . '" was not found in the settings.', 1458814468);
        }

        return $this->configurationRenderingService->computeConfiguration($settings[$pathToRender], $context);
    }
}
