<?php
namespace Neos\SiteKickstarter\Command;

/*
 * This file is part of the Neos.SiteKickstarter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Package\PackageManagerInterface;
use Neos\SiteKickstarter\Service\GeneratorService;

/**
 * Command controller for the Kickstart generator
 */
class KickstartCommandController extends CommandController
{
    /**
     * @var PackageManagerInterface
     * @Flow\Inject
     */
    protected $packageManager;

    /**
     * @var GeneratorService
     * @Flow\Inject
     */
    protected $generatorService;

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
            $this->outputLine('Package key "%s" is not valid. Only UpperCamelCase in the format "Vendor.PackageKey", please!', array($packageKey));
            $this->quit(1);
        }

        if ($this->packageManager->isPackageAvailable($packageKey)) {
            $this->outputLine('Package "%s" already exists.', array($packageKey));
            $this->quit(1);
        }

        $generatedFiles = $this->generatorService->generateSitePackage($packageKey, $siteName);
        $this->outputLine(implode(PHP_EOL, $generatedFiles));
    }
}
