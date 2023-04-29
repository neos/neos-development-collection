<?php
declare(strict_types=1);

namespace Neos\SiteKickstarter\Service;

/*
 * This file is part of the Neos.SiteKickstarter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Flow\Annotations as Flow;
use Neos\SiteKickstarter\Generator\SitePackageGeneratorInterface;

class SitePackageGeneratorNameService
{
    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param string $generatorClass fully qualified namespace
     */
    public function getNameOfSitePackageGenerator(string $generatorClass) : string
    {
        /**
         * @var $generator SitePackageGeneratorInterface
         */
        $generator = $this->objectManager->get($generatorClass);

        return $generator->getGeneratorName();
    }
}
