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

use Neos\Flow\Reflection\ReflectionService;
use Neos\Flow\Annotations as Flow;
use Neos\SiteKickstarter\Generator\SitePackageGeneratorInterface;

class SiteGeneratorCollectingService
{
    /**
     * @var ReflectionService
     * @Flow\Inject
     */
    protected $reflectionService;

    public function getAllGenerators(): array
    {
        return $this->reflectionService->getAllImplementationClassNamesForInterface(SitePackageGeneratorInterface::class);
    }
}