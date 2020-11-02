<?php

namespace Neos\SiteKickstarter\Generator;

/*
 * This file is part of the Neos.SiteKickstarter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Kickstarter\Service\GeneratorService;

abstract class AbstractSitePackageGenerator extends GeneratorService
{
    abstract public function generateSitePackage(string $packageKey, string $siteName);

    /**
     * returns the human readable name of the generator, by default it returns the class name of the sub-class
     *
     * @return string
     */
    public function getGeneratorName(): string
    {
        return get_class($this);
    }
}
