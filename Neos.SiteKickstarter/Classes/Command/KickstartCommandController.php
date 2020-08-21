<?php

namespace Neos\SiteKickstarter\Command;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Reflection\ReflectionService;
use Neos\SiteKickstarter\Annotation\SitePackageGenerator;
use Neos\SiteKickstarter\Generator\AbstractSitePackageGenerator;

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
     * @var ReflectionService
     * @Flow\Inject
     */
    protected $reflectionService;

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

        $generatorClasses = $this->reflectionService->getAllSubClassNamesForClass(AbstractSitePackageGenerator::class);

        $selection = [];
        $nameToClassMap = [];
        foreach ($generatorClasses as $generatorClass) {
            $classAnnotation = $this->reflectionService->getClassAnnotation($generatorClass, SitePackageGenerator::class);
            if ($classAnnotation instanceof SitePackageGenerator) {
                /**
                 * @var SitePackageGenerator $classAnnotation
                 */
                $name = $classAnnotation->generatorName;
            } else {
                $name = $generatorClass;
            }

            $selection[] = $name;
            $nameToClassMap[$name] = $generatorClass;
        }

        $generatorName = $this->output->select('What generator do you want to use?',
            $selection
        );

        $generatorClass = $nameToClassMap[$generatorName];

        $generatorService = $this->objectManager->get($generatorClass);

        $generatedFiles = $generatorService->generateSitePackage($packageKey, $siteName);
        $this->outputLine(implode(PHP_EOL, $generatedFiles));
    }
}
