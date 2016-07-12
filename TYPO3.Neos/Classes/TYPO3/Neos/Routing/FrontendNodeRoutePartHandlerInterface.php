<?php
namespace TYPO3\Neos\Routing;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Routing\DynamicRoutePartInterface;

/**
 * Marker interface which can be used to replace the currently used FrontendNodeRoutePartHandler,
 * to e.g. use the one with localization support.
 */
interface FrontendNodeRoutePartHandlerInterface extends DynamicRoutePartInterface
{
}
