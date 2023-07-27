<?php
declare(strict_types=1);
/*
 * This file is part of the Neos.SiteKickstarter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

namespace Neos\SiteKickstarter\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Package\PackageManager;
use Neos\SiteKickstarter\Service\SiteGeneratorCollectingService;
use Neos\SiteKickstarter\Service\SitePackageGeneratorNameService;

/**
 * Command controller for the Kickstart generator
 */
class KickstartCommandController extends CommandController
{
    /**
     * @var PackageManager
     * @Flow\Inject
     */
    protected $packageManager;

    /**
     * @var SiteGeneratorCollectingService
     * @Flow\Inject
     */
    protected $siteGeneratorCollectingService;

    /**
     * @var SitePackageGeneratorNameService
     * @Flow\Inject
     */
    protected $sitePackageGeneratorNameService;

    /**
     * Kickstart a new site package
     *
     * This command generates a new site package with basic Fusion and Sites.xml
     *
     * @param string $packageKey The packageKey for your site
     * @param string $siteName The siteName of your site
     * @return string
     */
    public function siteCommand($packageKey, $siteName)
    {
        if (!$this->packageManager->isPackageKeyValid($packageKey)) {
            $this->outputLine('Package key "%s" is not valid. Only UpperCamelCase in the format "Vendor.PackageKey", please!', [$packageKey]);
            $this->quit(1);
        }

        if ($this->packageManager->isPackageAvailable($packageKey)) {
            $this->outputLine('Package "%s" already exists.', [$packageKey]);
            $this->quit(1);
        }

        $generatorClasses = $this->siteGeneratorCollectingService->getAllGenerators();

        $selection = [];
        $nameToClassMap = [];
        foreach ($generatorClasses as $generatorClass) {
            $name = $this->sitePackageGeneratorNameService->getNameOfSitePackageGenerator($generatorClass);

            $selection[] = $name;
            $nameToClassMap[$name] = $generatorClass;
        }

        $generatorName = $this->output->select(
            sprintf('What generator do you want to use? (<info>%s</info>): ', array_key_first($selection)),
            $selection,
            array_key_first($selection)
        );

        $generatorClass = $nameToClassMap[$generatorName];

        $generatorService = $this->objectManager->get($generatorClass);

        $generatedFiles = $generatorService->generateSitePackage($packageKey, $siteName);
        $this->outputLine(implode(PHP_EOL, $generatedFiles));
    }
}
