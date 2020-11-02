<?php

namespace Neos\SiteKickstarter\Tests\Unit\Service\Fixtures;

/*
 * This file is part of the Neos.SiteKickstarter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\SiteKickstarter\Generator\AbstractSitePackageGenerator;

class AnnotatedSitePackageGenerator extends AbstractSitePackageGenerator
{
    public function generateSitePackage($packageKey, $siteName)
    {
        // just a dummy
    }

    public function getGeneratorName(): string
    {
        return 'AnnotatedSitePackageGenerator';
    }
}
