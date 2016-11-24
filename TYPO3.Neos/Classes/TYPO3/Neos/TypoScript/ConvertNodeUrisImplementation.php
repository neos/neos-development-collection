<?php
namespace TYPO3\Neos\TypoScript;

/*
 * This file is part of the TYPO3.Neos package.
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
 *   someTextProperty.@process.1 = TYPO3.Neos:ConvertNodeUris
 * @deprecated use ConvertUrisImplementation instead
 */
class ConvertNodeUrisImplementation extends ConvertUrisImplementation
{
}
