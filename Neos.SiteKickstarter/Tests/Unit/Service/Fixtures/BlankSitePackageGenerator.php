<?php

namespace Neos\SiteKickstarter\Tests\Fixtures;

use Neos\SiteKickstarter\Generator\AbstractSitePackageGenerator;

/**
 * Class AnnotatedSitePackageGenerator
 * @package Neos\SiteKickstarter\Tests\Fixtures
 *
 */
class BlankSitePackageGenerator extends AbstractSitePackageGenerator
{
    public function generateSitePackage($packageKey, $siteName)
    {
        // just a dummy
    }
}
