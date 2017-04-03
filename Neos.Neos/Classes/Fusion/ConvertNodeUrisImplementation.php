<?php
namespace Neos\Neos\Fusion;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;

/**
 * A TypoScript Object that converts Node references in the format "node://<UUID>" to proper URIs
 *
 * Usage::
 *
 *   someTextProperty.@process.1 = Neos.Neos:ConvertNodeUris
 * @deprecated use ConvertUrisImplementation instead
 */
class ConvertNodeUrisImplementation extends ConvertUrisImplementation
{
}
