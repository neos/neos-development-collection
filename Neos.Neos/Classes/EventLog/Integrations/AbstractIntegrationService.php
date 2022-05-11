<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\EventLog\Integrations;

use Neos\Flow\Annotations as Flow;
use Neos\Neos\EventLog\Domain\Service\EventEmittingService;

abstract class AbstractIntegrationService
{
    /**
     * @Flow\Inject
     * @var EventEmittingService
     */
    protected $eventEmittingService;
}
