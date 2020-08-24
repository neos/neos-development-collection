<?php

namespace Neos\SiteKickstarter\Service;

use Neos\Flow\Reflection\ReflectionService;
use Neos\SiteKickstarter\Annotation\SitePackageGenerator;
use Neos\Flow\Annotations as Flow;

class SitePackageGeneratorNameService
{
    /**
     * @var ReflectionService
     * @Flow\Inject
     */
    protected $reflectionService;

    /**
     * @param string $generatorClass fully qualified namespace
     */
    public function getNameOfSitePackageGenerator($generatorClass)
    {
        $name = $generatorClass;

        $classAnnotation = $this->reflectionService->getClassAnnotation(
            $generatorClass,
            SitePackageGenerator::class
        );
        if ($classAnnotation instanceof SitePackageGenerator) {
            /**
             * @var SitePackageGenerator $classAnnotation
             */
            $name = $classAnnotation->generatorName;
        }

        return $name;
    }
}