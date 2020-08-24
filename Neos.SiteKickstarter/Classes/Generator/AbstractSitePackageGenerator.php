<?php

namespace Neos\SiteKickstarter\Generator;

abstract class AbstractSitePackageGenerator extends \Neos\Kickstarter\Service\GeneratorService
{
    abstract public function generateSitePackage($packageKey, $siteName);
}
