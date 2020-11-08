<?php
declare(strict_types=1);

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

interface SitePackageGeneratorInterface
{
    /**
     * returns generated files as an array
     *
     * @param string $packageKey
     * @param string $siteName
     * @return array
     */
    public function generateSitePackage(string $packageKey, string $siteName): array;

    /**
     * returns the human readable name of the generator
     *
     * @return string
     */
    public function getGeneratorName(): string;
}
