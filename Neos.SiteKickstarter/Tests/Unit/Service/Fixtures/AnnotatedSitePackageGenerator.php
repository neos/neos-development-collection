<?php

namespace Neos\SiteKickstarter\Tests\Fixtures;

use Neos\SiteKickstarter\Generator\AbstractSitePackageGenerator;
use Neos\SiteKickstarter\Annotation as SiteKickstarter;

/**
 * Class AnnotatedSitePackageGenerator
 * @package Neos\SiteKickstarter\Tests\Fixtures
 *
 * @SiteKickstarter\SitePackageGenerator("AnnotatedSitePackageGenerator")
 */
class AnnotatedSitePackageGenerator extends AbstractSitePackageGenerator
{
    public function generateSitePackage($packageKey, $siteName)
    {
        // just a dummy
    }
}
